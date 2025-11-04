<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-key',
    description: 'Generate a new encryption key for sensitive data'
)]
class GenerateEncryptionKeyCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Generate a secure random key (32 bytes for AES-256)
        $key = bin2hex(random_bytes(32));

        $io->title('Encryption Key Generator');
        $io->success('New encryption key generated successfully!');
        
        $io->section('Add this line to your .env file:');
        $io->text("APP_ENCRYPTION_KEY=$key");
        
        $io->warning([
            'IMPORTANT SECURITY NOTES:',
            '1. Store this key securely - it cannot be recovered if lost',
            '2. Never commit this key to version control',
            '3. If you change this key, all encrypted data will become unreadable',
            '4. Backup your key in a secure location',
            '5. Rotate this key periodically (requires data re-encryption)'
        ]);

        $envPath = dirname(__DIR__, 2) . '/.env';
        
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            
            // Check if key already exists
            if (preg_match('/^APP_ENCRYPTION_KEY=/m', $envContent)) {
                $io->note('An encryption key already exists in .env file.');
                
                if ($io->confirm('Do you want to replace it? (This will make existing encrypted data unreadable)', false)) {
                    $envContent = preg_replace(
                        '/^APP_ENCRYPTION_KEY=.*/m',
                        "APP_ENCRYPTION_KEY=$key",
                        $envContent
                    );
                    file_put_contents($envPath, $envContent);
                    $io->success('.env file updated with new key!');
                    $io->warning('ATTENTION: You must re-encrypt all existing data!');
                } else {
                    $io->info('Operation cancelled. Existing key kept.');
                }
            } else {
                // Add key to .env
                if ($io->confirm('Do you want to add this key to your .env file?', true)) {
                    $envContent .= "\n# Encryption key for sensitive data (generated: " . date('Y-m-d H:i:s') . ")\n";
                    $envContent .= "APP_ENCRYPTION_KEY=$key\n";
                    file_put_contents($envPath, $envContent);
                    $io->success('.env file updated!');
                } else {
                    $io->info('Key not added. Please add it manually.');
                }
            }
        } else {
            $io->error('.env file not found! Please add the key manually.');
        }

        $io->section('Key Details:');
        $io->table(
            ['Property', 'Value'],
            [
                ['Algorithm', 'AES-256-CBC'],
                ['Key Length', '64 characters (32 bytes)'],
                ['Generated At', date('Y-m-d H:i:s')],
                ['Entropy Bits', '256 bits']
            ]
        );

        return Command::SUCCESS;
    }
}
