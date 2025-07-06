<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Exception;

class CommandController extends Controller
{
    /**
     * Available commands that can be executed
     */
    private const AVAILABLE_COMMANDS = [
        'fetch:orders' => [
            'name' => 'Siparişleri Çek',
            'description' => 'Tüm mağazalar için siparişleri çeker',
            'icon' => 'fas fa-download'
        ],
        'order:product-fetch' => [
            'name' => 'Ürün Bilgilerini Çek',
            'description' => 'Siparişlerdeki ürün bilgilerini çeker',
            'icon' => 'fas fa-box'
        ]
    ];

    /**
     * Execute a command
     */
    public function executeCommand(Request $request)
    {
        $request->validate([
            'command' => 'required|string|in:' . implode(',', array_keys(self::AVAILABLE_COMMANDS))
        ]);

        $command = $request->input('command');
        
        try {
            // Set timeout to 10 minutes
            set_time_limit(600);
            
            // Log command execution start
            Log::info("Command execution started: {$command}", [
                'user_id' => auth()->id(),
                'timestamp' => now()
            ]);

            // Execute the command
            $exitCode = Artisan::call($command);
            
            // Get command output
            $output = Artisan::output();
            
            // Log command execution end
            Log::info("Command execution completed: {$command}", [
                'user_id' => auth()->id(),
                'exit_code' => $exitCode,
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0 ? 'Komut başarıyla çalıştırıldı!' : 'Komut çalıştırılamadı!',
                'output' => $output,
                'exit_code' => $exitCode,
                'command_info' => self::AVAILABLE_COMMANDS[$command]
            ]);

        } catch (Exception $e) {
            Log::error("Command execution failed: {$command}", [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Komut çalıştırılırken hata oluştu: ' . $e->getMessage(),
                'output' => '',
                'exit_code' => -1,
                'command_info' => self::AVAILABLE_COMMANDS[$command]
            ], 500);
        }
    }

    /**
     * Get available commands
     */
    public function getAvailableCommands()
    {
        return response()->json([
            'commands' => self::AVAILABLE_COMMANDS
        ]);
    }
} 