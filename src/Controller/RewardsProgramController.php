<?php

namespace App\Controller;

use App\Entity\RewardsProgram;
use App\Entity\Student;
use App\Repository\RewardsProgramRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

class RewardsProgramController extends AbstractController
{
    #[Route('/', name: 'app_rewards_program_index', methods: ['GET'])]
    public function index(RewardsProgramRepository $repository): Response
    {
        $programs = $repository->findAll();

        return $this->render('rewards_program/index.html.twig', [
            'programs' => $programs,
        ]);
    }

    #[Route('/program/new', name: 'app_rewards_program_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('rewards_program/new.html.twig');
    }

    #[Route('/program', name: 'app_rewards_program_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $name = $request->request->get('name');

        if (!$name) {
            return $this->redirectToRoute('app_rewards_program_new');
        }

        $program = new RewardsProgram();
        $program->setName($name);

        $em->persist($program);
        $em->flush();

        return $this->redirectToRoute('app_rewards_program_show', ['id' => $program->getId()]);
    }

    #[Route('/program/{id}', name: 'app_rewards_program_show', methods: ['GET'])]
    public function show(RewardsProgram $program): Response
    {
        return $this->render('rewards_program/show.html.twig', [
            'program' => $program,
        ]);
    }

    #[Route('/program/{id}/add-student', name: 'app_rewards_program_add_student', methods: ['GET'])]
    public function addStudentForm(RewardsProgram $program): Response
    {
        return $this->render('rewards_program/add_student.html.twig', [
            'program' => $program,
        ]);
    }

    #[Route('/program/{id}/student', name: 'app_rewards_program_create_student', methods: ['POST'])]
    public function createStudent(RewardsProgram $program, Request $request, EntityManagerInterface $em): Response
    {
        $name = $request->request->get('name');

        if (!$name) {
            return $this->redirectToRoute('app_rewards_program_add_student', ['id' => $program->getId()]);
        }

        $student = new Student();
        $student->setName($name);
        $student->setRewardsProgram($program);

        $em->persist($student);
        $em->flush();

        return $this->redirectToRoute('app_rewards_program_show', ['id' => $program->getId()]);
    }

    #[Route('/program/{id}/random-winner', name: 'app_rewards_program_random_winner', methods: ['POST'])]
    public function randomWinner(RewardsProgram $program, Request $request): Response
    {
        $students = $program->getStudents();
        $winner = null;

        // Build weighted pool: each student gets entries equal to their points
        $pool = [];
        foreach ($students as $student) {
            $points = $student->getPoints();
            if ($points > 0) {
                for ($i = 0; $i < $points; $i++) {
                    $pool[] = $student;
                }
            }
        }

        if (!empty($pool)) {
            $winner = $pool[array_rand($pool)];
        }

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('rewards_program/_winner.stream.html.twig', [
                'winner' => $winner,
                'program' => $program,
            ]);
        }

        $this->addFlash('winner', $winner ? $winner->getName() : null);

        return $this->redirectToRoute('app_rewards_program_show', ['id' => $program->getId()]);
    }
}
