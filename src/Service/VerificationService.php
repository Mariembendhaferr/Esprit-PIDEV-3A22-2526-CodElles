<?php

namespace App\Service;

use App\Entity\VerificationLogs;
use App\Repository\UserRepository;
use App\Repository\VerificationLogsRepository;
use Doctrine\ORM\EntityManagerInterface;

class VerificationService
{
    public function __construct(
        private EmailService $emailService,
        private WhatsAppService $whatsAppService,
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private VerificationLogsRepository $verificationLogsRepository
    ) {}

    public function sendEmailVerificationCode(string $email, string $prenom): bool
    {
        $code = $this->generateCode();
        error_log("🔐 Generated verification code for {$email}: {$code}");
        $this->saveCodeByEmail($email, $code, 'email_verification');
        return $this->emailService->sendVerificationCode($email, $prenom, $code);
    }

    public function sendPasswordResetCode(string $email, string $prenom): bool
    {
        $code = $this->generateCode();
        error_log("🔑 Generated reset code for {$email}: {$code}");
        $this->saveCodeByEmail($email, $code, 'password_reset');
        return $this->emailService->sendPasswordResetCode($email, $prenom, $code);
    }

    public function sendPasswordResetSms(string $phone, string $prenom): bool
    {
        $code = $this->generateCode();
        
        // Clean phone number for database search
        $cleanForDb = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleanForDb, '216') && strlen($cleanForDb) === 11) {
            $cleanForDb = substr($cleanForDb, 3);
        }
        
        error_log("🔍 Searching for user with phone: {$cleanForDb}");
        
        $user = $this->userRepository->findOneBy(['telephone' => $cleanForDb]);
        
        if (!$user) {
            error_log("❌ No user found with phone: {$cleanForDb}");
            return false;
        }
        
        $this->saveCode($user->getIdUser(), $code, 'password_reset');
        error_log("🔑 Reset SMS code for {$phone}: {$code}");
        
        return $this->whatsAppService->sendResetCode($phone, $code, $prenom);
    }

    public function verifyEmailCode(string $email, string $code): bool
    {
        error_log("🔍 Verifying email code for {$email}: input='{$code}'");
        
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            error_log("❌ User not found for email: {$email}");
            return false;
        }
        return $this->verifyCode($user->getIdUser(), $code, 'email_verification');
    }

    public function verifyPasswordResetCode(int $userId, string $code): bool
    {
        return $this->verifyCode($userId, $code, 'password_reset');
    }

    private function verifyCode(int $userId, string $code, string $type): bool
    {
        // Find the LATEST unused code for this user/type
        $log = $this->verificationLogsRepository->findOneBy(
            [
                'user' => $userId,
                'codeType' => $type,
                'used' => false
            ],
            ['createdAt' => 'DESC']
        );

        if (!$log) {
            error_log("❌ No unused {$type} code found for user #{$userId}");
            return false;
        }

        error_log("📦 DB Code: '{$log->getCode()}', Expires: " . $log->getExpiresAt()->format('Y-m-d H:i:s') . ", Now: " . (new \DateTime())->format('Y-m-d H:i:s'));

        // Check code match (trim to avoid whitespace issues)
        if (trim($log->getCode()) !== trim($code)) {
            error_log("❌ Code mismatch! DB='{$log->getCode()}', Input='{$code}'");
            return false;
        }

        // Check expiration
        if ($log->getExpiresAt() < new \DateTime()) {
            error_log("❌ Code expired!");
            $log->setUsed(true);
            $this->em->flush();
            return false;
        }

        // ✅ Success!
        error_log("✅ Code verified successfully for user #{$userId}");
        $log->setUsed(true);
        $this->em->flush();
        return true;
    }

    private function saveCodeByEmail(string $email, string $code, string $type): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            error_log("❌ No user found with email {$email}");
            return;
        }
        $this->saveCode($user->getIdUser(), $code, $type);
    }

    private function saveCode(int $userId, string $code, string $type): void
    {
        $user = $this->userRepository->find($userId);
        if (!$user) return;

        // Invalidate ALL old unused codes of same type
        $oldLogs = $this->verificationLogsRepository->findBy([
            'user' => $user,
            'codeType' => $type,
            'used' => false
        ]);
        foreach ($oldLogs as $oldLog) {
            $oldLog->setUsed(true);
            error_log("♻️ Marked old {$type} code as used: {$oldLog->getCode()}");
        }

        $log = new VerificationLogs();
        $log->setUser($user);
        $log->setCode($code);
        $log->setCodeType($type);
        $log->setCreatedAt(new \DateTime());
        $log->setExpiresAt(new \DateTime('+15 minutes'));
        $log->setUsed(false);

        $this->em->persist($log);
        $this->em->flush();
        
        error_log("💾 Saved new {$type} code: {$code} for user #{$userId}");
    }

    private function generateCode(): string
    {
        // Ensure 6 digits with leading zeros
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}