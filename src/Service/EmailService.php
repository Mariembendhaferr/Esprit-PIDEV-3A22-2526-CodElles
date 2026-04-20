<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class EmailService
{
    private string $fromEmail = 'ghofranetv1@gmail.com';
    private string $fromName  = 'DOURA MONDO';

    public function __construct(private MailerInterface $mailer) {}

    public function sendVerificationCode(string $to, string $prenom, string $code): bool
    {
        return $this->send(
            $to,
            'Code de vérification - DOURA MONDO',
            $this->buildVerificationBody($prenom, $code)
        );
    }

    public function sendPasswordResetCode(string $to, string $prenom, string $code): bool
    {
        return $this->send(
            $to,
            'Réinitialisation de mot de passe - DOURA MONDO',
            $this->buildPasswordResetBody($prenom, $code)
        );
    }

    public function sendWelcomeEmail(string $to, string $prenom): bool
    {
        return $this->send(
            $to,
            'Bienvenue sur DOURA MONDO ! 🌍✈️',
            $this->buildWelcomeBody($prenom)
        );
    }

    public function sendRatingNotification(string $to, string $toName, string $fromName, int $stars): bool
    {
        return $this->send(
            $to,
            'Vous avez reçu une évaluation ! ⭐',
            $this->buildRatingBody($toName, $fromName, $stars)
        );
    }

    /**
     * 1. Thank You Email (When someone rates the app or a user)
     */
    public function sendThankYouForRating(string $to, string $prenom, int $stars, ?string $comment = null): bool
    {
        $subject = $stars >= 4 ? 'Merci pour votre évaluation ! 🌟' : 'Merci pour votre retour 🙏 - Doura Mondo';
        return $this->send($to, $subject, $this->buildThankYouRatingBody($prenom, $stars, $comment));
    }

    /**
     * 2. 5-Star Rating Notification (When a user RECEIVES 5 stars)
     */
    public function send5StarNotification(string $to, string $prenom, string $fromName): bool
    {
        return $this->send(
            $to,
            'Vous avez reçu une évaluation 5 étoiles ! ⭐⭐⭐⭐⭐',
            $this->build5StarNotificationBody($prenom, $fromName)
        );
    }

    /**
     * 3. Bad Review Feedback (When a user RECEIVES < 3 stars)
     */
    public function sendBadReviewFeedback(string $to, string $prenom, int $stars, string $fromName, ?string $comment = null): bool
    {
        return $this->send(
            $to,
            'Nous voulons améliorer votre expérience ✨ - Doura Mondo',
            $this->buildBadReviewFeedbackBody($prenom, $stars, $fromName, $comment)
        );
    }

    public function sendLowRatingFeedbackRequest(string $to, string $prenom, int $stars, ?string $comment = null): bool
    {
        return $this->send($to, 'Nous voulons améliorer votre expérience ✨ - Doura Mondo', $this->buildLowRatingFeedbackBody($prenom, $stars, $comment));
    }

    public function sendRatingWithCommentResponse(string $to, string $prenom, int $stars, string $comment): bool
    {
        return $this->send($to, 'Merci pour votre précieux retour 💬 - Doura Mondo', $this->buildRatingWithCommentBody($prenom, $stars, $comment));
    }

    // ========== CONSTRUCTEURS DES EMAILS ==========

    private function buildThankYouRatingBody(string $prenom, int $stars, ?string $comment = null): string
    {
        $prenom = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');
        $starsHtml = str_repeat('⭐', $stars) . str_repeat('☆', 5 - $stars);
        $starPercentage = ($stars / 5) * 100;
        
        $starsMessage = ($stars >= 4) 
            ? 'Nous sommes ravis que votre expérience ait été positive ! Continuez à explorer le monde avec nous.' 
            : 'Chaque retour nous aide à nous améliorer. Votre satisfaction est notre priorité absolue.';
        
        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Merci pour votre évaluation</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Segoe UI', 'Inter', system-ui, sans-serif; background: linear-gradient(135deg, #f5f0e6 0%, #e8dfcf 100%); margin: 0; padding: 40px 20px; }
                .email-container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 32px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
                .email-header { background: linear-gradient(135deg, #5C0000 0%, #8B0000 50%, #5C0000 100%); padding: 40px 30px; text-align: center; position: relative; overflow: hidden; }
                .email-header::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(201,168,76,0.15) 0%, transparent 70%); animation: rotate 20s linear infinite; }
                @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
                .email-header h1 { color: #C9A84C; font-size: 28px; font-weight: 800; letter-spacing: 2px; margin-bottom: 10px; position: relative; z-index: 1; }
                .stars-container { background: linear-gradient(135deg, #fff9e8, #fff2df); border-radius: 60px; padding: 15px 25px; display: inline-block; margin-top: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
                .stars { font-size: 32px; letter-spacing: 4px; }
                .star-percentage { margin-top: 10px; height: 8px; background: #e5e7eb; border-radius: 10px; overflow: hidden; width: 200px; }
                .star-percentage-fill { height: 100%; background: linear-gradient(90deg, #C9A84C, #E8C970); width: {$starPercentage}%; border-radius: 10px; animation: fillBar 1s ease-out; }
                @keyframes fillBar { from { width: 0; } to { width: {$starPercentage}%; } }
                .email-content { padding: 40px 35px; background: white; }
                .greeting { font-size: 24px; font-weight: 700; color: #2E1010; margin-bottom: 16px; font-family: 'Georgia', serif; }
                .message { color: #4a5568; line-height: 1.7; margin-bottom: 30px; font-size: 16px; }
                .quote-box { background: linear-gradient(135deg, #fef9e6, #fff6e0); border-left: 4px solid #C9A84C; padding: 20px 25px; border-radius: 16px; margin: 25px 0; font-style: italic; color: #5C0000; font-size: 15px; }
                .btn { display: inline-block; background: linear-gradient(135deg, #C9A84C, #E8C970); color: #2E1010 !important; padding: 14px 32px; border-radius: 50px; text-decoration: none; font-weight: 700; margin-top: 20px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(201,168,76,0.3); }
                .email-footer { background: #f8f7f4; padding: 25px 30px; text-align: center; color: #9A8060; font-size: 12px; border-top: 1px solid rgba(201,168,76,0.15); }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h1>✨ MERCI POUR VOTRE AVIS ✨</h1>
                    <div class="stars-container">
                        <div class="stars">{$starsHtml}</div>
                    </div>
                </div>
                <div class="email-content">
                    <div class="greeting">Bonjour {$prenom} ! 👋</div>
                    <div class="message">
                        <p>Nous tenons à vous remercier chaleureusement d'avoir pris le temps de partager votre expérience avec Doura Mondo.</p>
                        <p>Votre avis est précieux et nous aide à grandir et à nous améliorer chaque jour pour vous offrir la meilleure expérience de voyage possible.</p>
                    </div>
                    <div class="quote-box">💫 <strong>{$stars}/5 étoiles</strong><br>{$starsMessage}</div>
                    <div style="text-align: center;"><a href="http://localhost:8000" class="btn">🌍 Continuer l'aventure</a></div>
                </div>
                <div class="email-footer">
                    <div class="social-icons"><span>📱</span> <span>🐦</span> <span>📘</span> <span>📷</span></div>
                    <p>© 2025 DOURA MONDO - Voyagez Autrement</p>
                    <p style="margin-top: 8px;">Cet email a été envoyé suite à votre évaluation.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    private function build5StarNotificationBody(string $prenom, string $fromName): string
    {
        $prenom = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');
        $fromName = htmlspecialchars($fromName, ENT_QUOTES, 'UTF-8');
        
        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>5 Étoiles ! 🌟</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Segoe UI', 'Inter', system-ui, sans-serif; background: linear-gradient(135deg, #fff9e8 0%, #f5f0e6 100%); margin: 0; padding: 40px 20px; }
                .email-container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 32px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
                .email-header { background: linear-gradient(135deg, #C9A84C 0%, #E8C970 100%); padding: 50px 30px; text-align: center; position: relative; overflow: hidden; }
                .email-header::before { content: '🌟'; position: absolute; font-size: 150px; opacity: 0.1; top: -20px; right: -20px; transform: rotate(15deg); }
                .email-header h1 { color: #2E1010; font-size: 32px; font-weight: 800; margin-bottom: 10px; position: relative; z-index: 1; }
                .stars-big { font-size: 48px; letter-spacing: 8px; margin: 20px 0; text-shadow: 0 2px 10px rgba(201,168,76,0.3); }
                .email-content { padding: 40px 35px; }
                .greeting { font-size: 24px; font-weight: 700; color: #2E1010; margin-bottom: 16px; font-family: 'Georgia', serif; }
                .message { color: #4a5568; line-height: 1.7; margin-bottom: 30px; font-size: 16px; }
                .highlight-box { background: linear-gradient(135deg, #fef9e6, #fff6e0); border: 2px solid #C9A84C; padding: 25px; border-radius: 20px; margin: 25px 0; text-align: center; }
                .highlight-box strong { color: #5C0000; font-size: 18px; }
                .btn { display: inline-block; background: linear-gradient(135deg, #5C0000, #8B0000); color: white !important; padding: 14px 32px; border-radius: 50px; text-decoration: none; font-weight: 700; margin-top: 20px; box-shadow: 0 4px 15px rgba(92,0,0,0.3); }
                .email-footer { background: #f8f7f4; padding: 25px 30px; text-align: center; color: #9A8060; font-size: 12px; border-top: 1px solid rgba(201,168,76,0.15); }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h1>🎉 FÉLICITATIONS !</h1>
                    <div class="stars-big">⭐⭐⭐⭐⭐</div>
                </div>
                <div class="email-content">
                    <div class="greeting">Bravo {$prenom} ! 🏆</div>
                    <div class="message">
                        <p>Excellente nouvelle ! Vous venez de recevoir une évaluation <strong>5 étoiles</strong> de la part de <strong>{$fromName}</strong>.</p>
                        <p>Cela montre que vous êtes un membre exceptionnel de notre communauté. Continuez comme ça !</p>
                    </div>
                    <div class="highlight-box">
                        <strong>Votre excellence est reconnue !</strong><br>
                        <span style="color: #7A6050; font-size: 14px;">Merci de faire partie de l'aventure Doura Mondo.</span>
                    </div>
                    <div style="text-align: center;"><a href="http://localhost:8000/user/dashboard" class="btn">Voir mon profil</a></div>
                </div>
                <div class="email-footer">
                    <p>© 2025 DOURA MONDO - Voyagez Autrement</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    private function buildBadReviewFeedbackBody(string $prenom, int $stars, string $fromName, ?string $comment = null): string
    {
        $prenom = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');
        $fromName = htmlspecialchars($fromName, ENT_QUOTES, 'UTF-8');
        $commentText = $comment ? htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') : '';
        $starsHtml = str_repeat('⭐', $stars) . str_repeat('☆', 5 - $stars);
        
        $hasComment = !empty($commentText);
        $feedbackContent = '';
        if ($hasComment) {
            $feedbackContent = '<div style="background: white; padding: 15px; border-radius: 12px; border-left: 3px solid #C9A84C; font-style: italic; color: #5C0000; margin: 15px 0;">"' . $commentText . '"</div>';
        }
        
        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Nous voulons vous entendre</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Segoe UI', 'Inter', system-ui, sans-serif; background: linear-gradient(135deg, #fef5e8 0%, #f8e8d4 100%); margin: 0; padding: 40px 20px; }
                .email-container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 32px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
                .email-header { background: linear-gradient(135deg, #2E1010 0%, #5C0000 100%); padding: 40px 30px; text-align: center; }
                .email-header h1 { color: #C9A84C; font-size: 28px; font-weight: 800; margin-bottom: 15px; }
                .stars-display { font-size: 32px; letter-spacing: 4px; background: rgba(255,255,255,0.1); display: inline-block; padding: 12px 24px; border-radius: 50px; }
                .email-content { padding: 40px 35px; }
                .greeting { font-size: 24px; font-weight: 700; color: #2E1010; margin-bottom: 20px; }
                .message { color: #4a5568; line-height: 1.7; margin-bottom: 25px; font-size: 16px; }
                .feedback-box { background: #FFF9EE; border: 1px solid rgba(201,168,76,0.3); border-radius: 20px; padding: 25px; margin: 25px 0; }
                .feedback-box h4 { color: #C9A84C; margin-bottom: 12px; font-size: 18px; }
                .btn-support { display: inline-block; background: #5C0000; color: white !important; padding: 14px 32px; border-radius: 50px; text-decoration: none; font-weight: 700; transition: all 0.3s; margin-top: 15px; }
                .email-footer { background: #f8f7f4; padding: 25px 30px; text-align: center; color: #9A8060; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h1>🙏 NOUS VOULONS VOUS ENTENDRE</h1>
                    <div class="stars-display">{$starsHtml}</div>
                </div>
                <div class="email-content">
                    <div class="greeting">Cher {$prenom},</div>
                    <div class="message">
                        <p>Nous avons remarqué que votre évaluation n'est pas tout à fait à la hauteur de vos attentes.</p>
                        <p><strong>Votre opinion compte énormément pour nous.</strong> Nous sommes sincèrement désolés que votre expérience n'ait pas été parfaite.</p>
                    </div>
                    <div class="feedback-box">
                        <h4>💬 À propos de votre expérience avec {$fromName} :</h4>
                        {$feedbackContent}
                        <p style="margin-top: 15px; color: #7A6050; font-size: 14px;">
                            Nous prenons chaque retour très au sérieux. Notre équipe va analyser votre suggestion et reviendra vers vous sous 48h.
                        </p>
                    </div>
                    <div class="message">
                        <p>Nous sommes à votre écoute et nous engageons à répondre à chaque retour dans les plus brefs délais.</p>
                        <p>Votre satisfaction est notre priorité absolue. ✨</p>
                    </div>
                    <div style="text-align: center;">
                        <a href="http://localhost:8000/support" class="btn-support">📧 Contacter le support</a>
                    </div>
                </div>
                <div class="email-footer">
                    <p>© 2025 DOURA MONDO - À votre écoute</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    private function buildLowRatingFeedbackBody(string $prenom, int $stars, ?string $comment = null): string
    {
        $prenom = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');
        $starsHtml = str_repeat('⭐', $stars) . str_repeat('☆', 5 - $stars);
        $hasComment = !empty($comment);
        $commentText = $hasComment ? htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') : '';
        
        $feedbackContent = '';
        if ($hasComment) {
            $feedbackContent = '<div class="feedback-text"><strong>Votre commentaire :</strong><br>"' . $commentText . '"</div><p style="margin-top: 15px;">Nous avons bien reçu votre retour. Notre équipe va analyser votre suggestion et reviendra vers vous sous 48h.</p>';
        } else {
            $feedbackContent = '<p>Vous n\'avez pas laissé de commentaire. <strong>Partagez-nous ce qui n\'a pas fonctionné</strong> et nous ferons tout pour améliorer votre expérience.</p><div style="text-align: center; margin-top: 20px;"><a href="https://dourmondo.com/feedback" class="btn-feedback">📝 Partager mon retour</a></div>';
        }
        
        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Nous voulons vous entendre</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Segoe UI', 'Inter', system-ui, sans-serif; background: linear-gradient(135deg, #fef5e8 0%, #f8e8d4 100%); margin: 0; padding: 40px 20px; }
                .email-container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 32px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
                .email-header { background: linear-gradient(135deg, #2E1010 0%, #5C0000 100%); padding: 40px 30px; text-align: center; }
                .email-header h1 { color: #C9A84C; font-size: 28px; font-weight: 800; margin-bottom: 15px; }
                .stars-display { font-size: 32px; letter-spacing: 4px; background: rgba(255,255,255,0.1); display: inline-block; padding: 12px 24px; border-radius: 50px; }
                .email-content { padding: 40px 35px; }
                .greeting { font-size: 24px; font-weight: 700; color: #2E1010; margin-bottom: 20px; }
                .message { color: #4a5568; line-height: 1.7; margin-bottom: 25px; font-size: 16px; }
                .feedback-box { background: #FFF9EE; border: 1px solid rgba(201,168,76,0.3); border-radius: 20px; padding: 25px; margin: 25px 0; }
                .feedback-box h4 { color: #C9A84C; margin-bottom: 12px; font-size: 18px; }
                .feedback-text { background: white; padding: 15px; border-radius: 12px; border-left: 3px solid #C9A84C; font-style: italic; color: #5C0000; }
                .btn-feedback { display: inline-block; background: #5C0000; color: white; padding: 14px 32px; border-radius: 50px; text-decoration: none; font-weight: 700; transition: all 0.3s; }
                .btn-feedback:hover { background: #8B0000; transform: translateY(-2px); }
                .email-footer { background: #f8f7f4; padding: 25px 30px; text-align: center; color: #9A8060; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h1>🙏 NOUS VOULONS VOUS ENTENDRE</h1>
                    <div class="stars-display">{$starsHtml}</div>
                </div>
                <div class="email-content">
                    <div class="greeting">Cher {$prenom},</div>
                    <div class="message">
                        <p>Nous avons remarqué que votre évaluation n'est pas tout à fait à la hauteur de vos attentes.</p>
                        <p><strong>Votre opinion compte énormément pour nous.</strong> Nous sommes sincèrement désolés que votre expérience n'ait pas été parfaite.</p>
                    </div>
                    <div class="feedback-box">
                        <h4>💬 Aidez-nous à nous améliorer</h4>
                        {$feedbackContent}
                    </div>
                    <div class="message">
                        <p>Nous sommes à votre écoute et nous engageons à répondre à chaque retour dans les plus brefs délais.</p>
                        <p>Votre satisfaction est notre priorité absolue. ✨</p>
                    </div>
                </div>
                <div class="email-footer">
                    <p>© 2025 DOURA MONDO - À votre écoute</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    private function buildRatingWithCommentBody(string $prenom, int $stars, string $comment): string
    {
        $prenom = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');
        $comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');
        $starsHtml = str_repeat('⭐', $stars) . str_repeat('☆', 5 - $stars);
        
        $responseMessage = ($stars >= 3) ? 'Nous prenons en compte votre suggestion avec la plus grande attention. Un membre de notre équipe va analyser votre retour et reviendra vers vous très prochainement.' : 'Nous prenons votre retour très au sérieux. Notre équipe de support va analyser votre situation et vous contacter dans les plus brefs délais pour trouver une solution ensemble.';
        
        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Merci pour votre commentaire</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Segoe UI', 'Inter', system-ui, sans-serif; background: linear-gradient(135deg, #e8e0cf 0%, #f5ede0 100%); margin: 0; padding: 40px 20px; }
                .email-container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 32px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
                .email-header { background: linear-gradient(135deg, #5C0000, #8B0000); padding: 35px 30px; text-align: center; }
                .email-header h1 { color: #C9A84C; font-size: 26px; font-weight: 800; }
                .email-content { padding: 40px 35px; }
                .greeting { font-size: 24px; font-weight: 700; color: #2E1010; margin-bottom: 20px; }
                .comment-card { background: linear-gradient(135deg, #fef9e6, #fff5e6); border-radius: 20px; padding: 25px; margin: 25px 0; border: 1px solid rgba(201,168,76,0.2); }
                .comment-card .stars { font-size: 24px; margin-bottom: 15px; }
                .comment-text { font-style: italic; color: #5C0000; line-height: 1.6; font-size: 15px; border-left: 3px solid #C9A84C; padding-left: 20px; }
                .btn-contact { display: inline-block; background: #C9A84C; color: #2E1010; padding: 12px 28px; border-radius: 50px; text-decoration: none; font-weight: 700; margin-top: 20px; }
                .email-footer { background: #f8f7f4; padding: 25px; text-align: center; color: #9A8060; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h1>💬 VOTRE VOIX COMPTE</h1>
                </div>
                <div class="email-content">
                    <div class="greeting">Merci {$prenom} !</div>
                    <div class="comment-card">
                        <div class="stars">{$starsHtml}</div>
                        <div class="comment-text">"{$comment}"</div>
                    </div>
                    <p style="color: #4a5568; line-height: 1.6;">{$responseMessage}</p>
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="https://dourmondo.com/support" class="btn-contact">📧 Contacter le support</a>
                    </div>
                </div>
                <div class="email-footer">
                    <p>© 2025 DOURA MONDO - Ensemble, voyageons mieux</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    private function send(string $to, string $subject, string $html): bool
    {
        try {
            $email = (new Email())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($to)
                ->subject($subject)
                ->html($html);
            $this->mailer->send($email);
            error_log("✅ Email sent to {$to} — Subject: {$subject}");
            return true;
        } catch (\Exception $e) {
            error_log('❌ Email error: ' . $e->getMessage());
            return false;
        }
    }

    private function buildVerificationBody(string $prenom, string $code): string
    {
        $prenom = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');
        $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family:'Segoe UI',Arial,sans-serif; background:#f4f4f4; margin:0; padding:20px; }
                .container { max-width:600px; margin:0 auto; background:white; border-radius:15px; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
                .header { background:linear-gradient(135deg,#780000,#a00000); color:white; padding:30px; text-align:center; }
                .content { padding:40px 30px; text-align:center; }
                .code-box { background:linear-gradient(135deg,#eedbb5,#f5e6c8); border-radius:10px; padding:25px; margin:25px 0; }
                .code { font-size:36px; font-weight:bold; color:#780000; letter-spacing:8px; }
                .footer { background:#f8f9fa; padding:20px; text-align:center; color:#666; font-size:12px; }
                .warning { color:#dc3545; font-size:14px; margin-top:20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header"><h1>🌍 DOURA MONDO</h1></div>
                <div class="content">
                    <h2>Bonjour {$prenom} !</h2>
                    <p>Merci de vérifier votre adresse email.</p>
                    <p>Voici votre code de vérification :</p>
                    <div class="code-box"><span class="code">{$code}</span></div>
                    <p class="warning">⚠️ Ce code expire dans 15 minutes.</p>
                    <p>Si vous n'avez pas demandé ce code, ignorez cet email.</p>
                </div>
                <div class="footer"><p>© 2025 DOURA MONDO - Tous droits réservés</p></div>
            </div>
        </body>
        </html>
        HTML;
    }

    private function buildPasswordResetBody(string $prenom, string $code): string
    {
        $prenom = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');
        $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family:'Segoe UI',Arial,sans-serif; background:#f4f4f4; margin:0; padding:20px; }
                .container { max-width:600px; margin:0 auto; background:white; border-radius:15px; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
                .header { background:linear-gradient(135deg,#446415,#5a8520); color:white; padding:30px; text-align:center; }
                .content { padding:40px 30px; text-align:center; }
                .code-box { background:linear-gradient(135deg,#eedbb5,#f5e6c8); border-radius:10px; padding:25px; margin:25px 0; }
                .code { font-size:36px; font-weight:bold; color:#780000; letter-spacing:8px; }
                .footer { background:#f8f9fa; padding:20px; text-align:center; color:#666; font-size:12px; }
                .warning { color:#dc3545; font-size:14px; margin-top:20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header"><h1>🔒 Réinitialisation de mot de passe</h1></div>
                <div class="content">
                    <h2>Bonjour {$prenom} !</h2>
                    <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
                    <p>Utilisez ce code pour créer un nouveau mot de passe :</p>
                    <div class="code-box"><span class="code">{$code}</span></div>
                    <p class="warning">⚠️ Ce code expire dans 15 minutes.</p>
                    <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
                </div>
                <div class="footer"><p>© 2025 DOURA MONDO - Tous droits réservés</p></div>
            </div>
        </body>
        </html>
        HTML;
    }

    private function buildWelcomeBody(string $prenom): string
    {
        $prenom = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');
        
        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Bienvenue sur DOURA MONDO</title>
            <style>
                /* Reset styles */
                body, table, td, p, a, li, blockquote { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
                body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
                table { border-spacing: 0; }
                
                /* Main Container */
                .email-container {
                    max-width: 600px;
                    margin: 40px auto;
                    background: #ffffff;
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                }

                /* Header Gradient */
                .header {
                    background: linear-gradient(135deg, #5C0000 0%, #8B0000 100%);
                    padding: 40px 30px;
                    text-align: center;
                    position: relative;
                }
                .header::before {
                    content: '';
                    position: absolute;
                    top: 0; left: 0; right: 0; bottom: 0;
                    background-image: radial-gradient(circle at 50% 50%, rgba(201,168,76,0.15) 0%, transparent 70%);
                    pointer-events: none;
                }
                .logo {
                    font-family: 'Georgia', serif;
                    font-size: 32px;
                    font-weight: bold;
                    color: #C9A84C;
                    margin: 0;
                    letter-spacing: 2px;
                    position: relative;
                    z-index: 1;
                }
                .subtitle {
                    color: rgba(255,255,255,0.8);
                    font-size: 14px;
                    margin-top: 10px;
                    letter-spacing: 1px;
                    position: relative;
                    z-index: 1;
                }

                /* Content */
                .content {
                    padding: 40px 30px;
                    text-align: center;
                    color: #333333;
                }
                .greeting {
                    font-size: 24px;
                    font-weight: 700;
                    color: #2E1010;
                    margin-bottom: 20px;
                    font-family: 'Georgia', serif;
                }
                .message {
                    font-size: 16px;
                    line-height: 1.6;
                    color: #555555;
                    margin-bottom: 30px;
                }
                
                /* Feature Grid */
                .features {
                    display: flex;
                    justify-content: space-around;
                    margin: 30px 0;
                    flex-wrap: wrap;
                }
                .feature-item {
                    flex: 1;
                    min-width: 140px;
                    margin: 10px;
                    padding: 15px;
                    background: #FFF9EE;
                    border-radius: 12px;
                    border: 1px solid rgba(201,168,76,0.2);
                }
                .feature-icon { font-size: 30px; margin-bottom: 10px; display: block; }
                .feature-title { font-weight: 600; color: #7A6030; font-size: 14px; }

                /* Button */
                .btn-container {
                    margin-top: 30px;
                    text-align: center;
                }
                .btn {
                    display: inline-block;
                    background: linear-gradient(135deg, #C9A84C 0%, #E8C97A 100%);
                    color: #2E1010 !important;
                    text-decoration: none;
                    padding: 15px 40px;
                    border-radius: 50px;
                    font-weight: bold;
                    font-size: 16px;
                    box-shadow: 0 4px 15px rgba(201,168,76,0.4);
                    transition: transform 0.2s;
                }
                .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(201,168,76,0.6);
                }

                /* Footer */
                .footer {
                    background: #f8f7f4;
                    padding: 25px;
                    text-align: center;
                    font-size: 12px;
                    color: #9A8060;
                    border-top: 1px solid rgba(201,168,76,0.1);
                }
                .footer a { color: #C9A84C; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <!-- Header -->
                <div class="header">
                    <h1 class="logo">🌍 DOURA MONDO</h1>
                    <div class="subtitle">VOYAGEZ AUTREMENT</div>
                </div>

                <!-- Body -->
                <div class="content">
                    <div class="greeting">Bienvenue, {$prenom} ! 🎉</div>
                    <div class="message">
                        Nous sommes ravis de vous compter parmi nous. Votre aventure commence maintenant.<br><br>
                        Découvrez une communauté de voyageurs passionnés, des destinations exclusives et des expériences inoubliables.
                    </div>

                    <!-- Features -->
                    <div class="features">
                        <div class="feature-item">
                            <span class="feature-icon">✈️</span>
                            <div class="feature-title">Voyages Uniques</div>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">👥</span>
                            <div class="feature-title">Communauté</div>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">⭐</span>
                            <div class="feature-title">Avis & Notes</div>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <div class="btn-container">
                        <a href="http://localhost:8000" class="btn">Commencer l'aventure </a>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer">
                    <p>© 2025 DOURA MONDO - Tous droits réservés</p>
                    <p>Cet email a été envoyé automatiquement. Veuillez ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    private function buildRatingBody(string $toName, string $fromName, int $stars): string
    {
        $toName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $fromName = htmlspecialchars($fromName, ENT_QUOTES, 'UTF-8');
        $starsHtml = str_repeat('⭐', $stars);
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <body style="font-family:Arial; padding:20px;">
            <h2 style="color:#780000;">Bonjour {$toName},</h2>
            <p><strong>{$fromName}</strong> vous a attribué une note de <strong style="color:gold; font-size:20px;">{$starsHtml} ({$stars}/5)</strong>.</p>
            <p>Merci de faire partie de la communauté DOURA MONDO !</p>
            <p>À bientôt,<br>L'équipe DOURA MONDO</p>
        </body>
        </html>
        HTML;
    }
}