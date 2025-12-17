<?php

namespace App\Controller;

use App\Entity\Student;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

class StudentController extends AbstractController
{
    #[Route('/student/{id}/add-point', name: 'app_student_add_point', methods: ['POST'])]
    public function addPoint(Student $student, Request $request, EntityManagerInterface $em): Response
    {
        $student->addPoint();
        $em->flush();

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('student/_student_button.stream.html.twig', [
                'student' => $student,
            ]);
        }

        return $this->redirectToRoute('app_rewards_program_show', [
            'id' => $student->getRewardsProgram()->getId(),
        ]);
    }
}
