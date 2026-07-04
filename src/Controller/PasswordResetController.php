<?php

namespace App\Controller;

use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordType;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PasswordResetController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password')]
    // Fonction qui affiche le formulaire de demande de reset mot de passe. 
    public function request(Request $request, PasswordResetService $passwordResetService): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = (string) $form->get('email')->getData();
            $passwordResetService->requestReset($email);

            $this->addFlash('success', 'Si cet email existe, un lien de réinitialisation a été envoyé.');

            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('security/forgot_password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    // Fonction qui permet de définir un nouveau mot de passe via token. 
    public function reset(string $token, Request $request, PasswordResetService $passwordResetService): Response
    {
        $agent = $passwordResetService->findAgentByToken($token);
        if (!$agent) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou expiré.');

            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $passwordResetService->resetPassword($agent, (string) $form->get('plainPassword')->getData());
            $this->addFlash('success', 'Mot de passe mis à jour. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form,
        ]);
    }
}
