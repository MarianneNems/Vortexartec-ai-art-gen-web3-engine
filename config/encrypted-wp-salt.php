<?php
/**
 * Encrypted File: wp-salt.php
 * 
 * This file contains encrypted sensitive data.
 * Original file: wp-salt.php
 * Encrypted on: 2025-07-21 00:11:46
 * File size: 1016 bytes
 * 
 * DO NOT MODIFY THIS FILE MANUALLY!
 * Use the decryption utility to access the original content.
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('VORTEX_DECRYPT_MODE')) {
    http_response_code(403);
    exit('Access denied');
}

// Encrypted data
$encrypted_data = 'jpr0XIMvbGnPy2Qt1vDgGuHWIAnRVYVHB+cxGXcDjtKY3F6yo8vGPicPXzPnZCn9I8fGfxVsjIDAFjwwz+8vSuZ24cHILEJeqiKOtlR0GgdtGL7D3J5HhAU9HeJQ+PTX76yyrXD7KFHka0GynAEWMIJfDlEGk6JLeJvZWGzZAjt+r4mUDMlaJBPMEqjEjgu62ZAIFki1xpW/lSjrRQs/RRUmfhQk7jQqzVuKeTI+QRKTNs0+KHZpvsrHZQfyeR8MsKfAJT3GH/g65HEqCp3SteXQs2MG3b0xdUZN+p3DzkuGGokMAKVdKjcIu5BmBgtW9ZG95MqcCTJmEwLneV5dCQmJ4VN9flrz5H7cniStx5+q8/PerszBs1uYdzE2bp7pMsr/Bm96O1yyvnWUh095G8CxDHzPpbuKWL92Qi2YsINt4adNgyyLvuRBgLEMGpwhnIr5TKOcpI8sT4A3oii0cit64McKLXQGUjKf3L2PDtRBA1yT8QahhItgM/GIOV1Ny2sajoHS5a2SrlatvgGQFUzERITXRo2Ln13vlybYiRQz5ctDqJqCMrzpGKxTGjylK40LCo2Hdeu1ScVeLVi+PEZKiQT/pPrggZTDmIl1sSFPBn5Wp34C6epMDFE/vFo9BofTPs099BA12om8/KQCdYD+5Ss9A2IgiJLEpySXOtjYXAstjsviB3eHaJRj0T6alcFmXLmBLvxfeZzDPU3wWMRFVl4LswpN4BbfCIX9iIvYb/5JjJbOc0PkEKrEKZ/PIhwvnE/AHiRjncF99rTH7xrUHKcCJJwGuv+xG64iIhdXE6pzFVFwp33XSitxA83oi9f/ASRbAaXDjylmZj4xHMwzICCu0+icZeNWv+lhi7wjXOTsvEBekw7F3w0lqNmFfA0YI4aZnBE5KnsaCR2foADOsVkzNXRwuvRdg0Vqq214ErgjWwhycjBccbqM30loH7vgpfQd6GRIPJ7NnhQ8Lfvv89tNQ/XuPJ65AlleGkgSd8FkQDEgpQ==';

// Decryption function
function vortex_decrypt_file_data($encrypted_data, $key) {
    $data = base64_decode($encrypted_data);
    $iv = substr($data, 0, 16);
    $tag = substr($data, 16, 16);
    $encrypted = substr($data, 32);
    
    return openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
}

// Return encrypted data for decryption
return $encrypted_data;
