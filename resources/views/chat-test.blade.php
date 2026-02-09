<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Service - Test Interface</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
    <style>
        .messages-container {
            height: calc(100vh - 400px);
            overflow-y: auto;
        }
        .messages-container::-webkit-scrollbar {
            width: 8px;
        }
        .messages-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .messages-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        .message-sent {
            background-color: #dcf8c6;
            margin-left: auto;
        }
        .message-received {
            background-color: #ffffff;
            margin-right: auto;
        }
        .typing-indicator-bubble {
            position: relative;
            background-color: #F0F0F0;
            padding: 6px 9px;
            border-radius: 18px;
            display: inline-block;
            margin-right: auto;
            margin-left: 0;
            max-width: 80px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .typing-indicator-bubble::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: -6px;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 12px 12px 0;
            border-color: transparent #F0F0F0 transparent transparent;
        }

        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
        }

        .typing-indicator span {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out;
        }

        .typing-indicator span:nth-child(1) {
            background-color: #D0D0D0;
            animation-delay: 0s;
        }

        .typing-indicator span:nth-child(2) {
            background-color: #A0A0A0;
            animation-delay: 0.2s;
        }

        .typing-indicator span:nth-child(3) {
            background-color: #505050;
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 100% {
                opacity: 0.3;
                transform: translateY(0);
            }
            50% {
                opacity: 1;
                transform: translateY(-4px);
            }
        }

        /* Modern Badge Styles */
        .badge-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 11px;
            line-height: 1;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 9px;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.25);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: badge-appear 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform-origin: center;
        }

        .badge-normal {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .badge-large {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            min-width: 24px;
            height: 22px;
            padding: 0 7px;
            font-size: 12px;
        }

        .badge-pulse {
            animation: badge-pulse 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes badge-appear {
            0% {
                transform: scale(0.4);
                opacity: 0;
            }
            50% {
                transform: scale(1.15);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes badge-pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.25);
                box-shadow: 0 4px 16px rgba(239, 68, 68, 0.4);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 2px 8px rgba(239, 68, 68, 0.25);
            }
        }

        .conversation-item {
            transition: all 0.2s ease;
        }

        .conversation-item:hover {
            transform: translateX(3px);
        }

        /* ✅ NOUVEAU : Animation pour le statut "Vu" */
        .read-status-appear {
            animation: read-status-appear 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes read-status-appear {
            0% {
                opacity: 0;
                transform: translateX(-10px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Animation pulse personnalisée */
        .animate-pulse-custom {
            animation: pulse-custom 1s ease-in-out;
        }

        @keyframes pulse-custom {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
                transform: scale(1.05);
            }
        }

        /* Style pour le statut "Vu" WhatsApp */
        .seen-status {
            color: #3b82f6;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .seen-status::before {
            content: '✓✓';
            font-size: 10px;
            color: #3b82f6;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(2px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes seenAppear {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .seen-status-animated {
            animation: seenAppear 0.4s ease-out;
        }

        /* =====================================================
           STYLES POUR L'UPLOAD D'IMAGES
           ===================================================== */
        .image-upload-container {
            position: relative;
        }

        .image-upload-button {
            padding: 8px 12px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .image-upload-button:hover {
            background: #e5e7eb;
        }

        .image-upload-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .image-preview {
            display: none;
            position: relative;
            max-width: 150px;
            margin-bottom: 8px;
            border-radius: 8px;
            overflow: hidden;
        }

        .image-preview.visible {
            display: block;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 150px;
            object-fit: contain;
        }

        .image-preview-remove {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 24px;
            height: 24px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.2s;
        }

        .image-preview-remove:hover {
            background: rgba(220, 38, 38, 1);
            transform: scale(1.1);
        }

        .uploading-overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 8px;
        }

        .uploading-overlay.visible {
            display: flex;
        }

        .uploading-spinner {
            width: 32px;
            height: 32px;
            border: 3px solid #e5e7eb;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* =====================================================
           STYLES POUR L'ENREGISTREMENT AUDIO
           ===================================================== */
        .audio-record-button {
            padding: 8px 12px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .audio-record-button:hover {
            background: #e5e7eb;
        }

        .audio-record-button.recording {
            background: #ef4444;
            color: white;
            animation: pulse-red 1.5s ease-in-out infinite;
        }

        .audio-record-button.recording svg {
            color: white;
        }

        @keyframes pulse-red {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
            }
        }

        .audio-preview {
            display: none;
            position: relative;
            max-width: 400px;
            margin-bottom: 8px;
            border-radius: 8px;
            overflow: hidden;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 12px;
        }

        .audio-preview.visible {
            display: block;
        }

        .audio-player-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .audio-preview audio {
            flex: 1;
            height: 40px;
        }

        .audio-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .audio-duration {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }

        .audio-preview-remove {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 24px;
            height: 24px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.2s;
        }

        .audio-preview-remove:hover {
            background: rgba(220, 38, 38, 1);
            transform: scale(1.1);
        }

        .recording-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .recording-overlay.visible {
            display: flex;
        }

        .recording-content {
            background: white;
            padding: 32px;
            border-radius: 16px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        .recording-indicator {
            margin-bottom: 20px;
        }

        .recording-dot {
            display: inline-block;
            width: 20px;
            height: 20px;
            background: #ef4444;
            border-radius: 50%;
            animation: pulse-red 1.5s ease-in-out infinite;
        }

        .recording-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1f2937;
        }

        .recording-timer {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 24px;
            color: #1f2937;
            font-variant-numeric: tabular-nums;
        }

        .recording-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .recording-stop-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .recording-stop-btn:hover {
            background: #dc2626;
            transform: scale(1.05);
        }

        .recording-cancel-btn {
            padding: 12px 24px;
            background: #e5e7eb;
            color: #1f2937;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .recording-cancel-btn:hover {
            background: #d1d5db;
        }

        /* =====================================================
           STYLES POUR LES MESSAGES AUDIO
           ===================================================== */
        .audio-message {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .audio-message audio {
            flex: 1;
            height: 40px;
        }

        .audio-duration-badge {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            background: #e5e7eb;
            padding: 4px 8px;
            border-radius: 4px;
            white-space: nowrap;
        }

        /* =====================================================
           STYLES POUR LA COMPRESSION D'IMAGES
           ===================================================== */
        .compression-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .compression-overlay.visible {
            display: flex;
        }

        .compression-modal {
            background: white;
            padding: 32px 40px;
            border-radius: 16px;
            text-align: center;
            max-width: 360px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: compressionModalIn 0.3s ease-out;
        }

        @keyframes compressionModalIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .compression-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        .compression-icon svg {
            width: 32px;
            height: 32px;
            color: white;
        }

        .compression-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .compression-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 20px;
        }

        .compression-progress {
            background: #e5e7eb;
            border-radius: 8px;
            height: 8px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .compression-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6 0%, #8b5cf6 100%);
            border-radius: 8px;
            width: 0%;
            transition: width 0.3s ease-out;
        }

        .compression-status {
            font-size: 13px;
            color: #9ca3af;
        }

        .compression-info {
            margin-top: 16px;
            padding: 12px;
            background: #f3f4f6;
            border-radius: 8px;
            font-size: 12px;
            color: #6b7280;
        }

        .compression-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .compression-info-row:last-child {
            margin-bottom: 0;
        }

        .compression-info-label {
            font-weight: 500;
        }

        .compression-info-value {
            color: #374151;
        }

        .compression-info-value.success {
            color: #059669;
            font-weight: 600;
        }

        /* Styles pour les messages d'erreur de validation */
        .validation-error {
            display: none;
            background: #fee2e2;
            color: #991b1b;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            margin-top: 8px;
            animation: slideDown 0.3s ease-out;
        }

        .validation-error.visible {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Styles pour les messages contenant des images */
        .message-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            margin-top: 8px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .message-image:hover {
            transform: scale(1.02);
        }

        /* Modal de visualisation d'image */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .image-modal.visible {
            display: flex;
        }

        .image-modal img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 8px;
        }

        .image-modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .image-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        /* =====================================================
           STYLES POUR L'ÉDITION DE MESSAGES
           ===================================================== */
        .edit-button,
        .delete-button {
            opacity: 0;
            transition: opacity 0.2s;
            padding: 4px 8px;
            background: rgba(0, 0, 0, 0.05);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            color: #6b7280;
            margin-left: 8px;
        }

        .edit-button:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #3b82f6;
        }

        .delete-button:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .message-bubble:hover .edit-button,
        .message-bubble:hover .delete-button {
            opacity: 1;
        }

        /* Styles pour les messages supprimés */
        .message-deleted {
            font-style: italic;
            color: #9ca3af !important;
            background: #f3f4f6 !important;
        }

        .message-deleted-content {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #9ca3af;
            font-style: italic;
        }

        .message-deleted-content svg {
            width: 16px;
            height: 16px;
            opacity: 0.6;
        }

        /* Modal de confirmation de suppression */
        .delete-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .delete-modal-overlay.visible {
            display: flex;
        }

        .delete-modal {
            background: white;
            padding: 24px;
            border-radius: 16px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: modalIn 0.2s ease-out;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .delete-modal-icon {
            width: 56px;
            height: 56px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .delete-modal-icon svg {
            width: 28px;
            height: 28px;
            color: #ef4444;
        }

        .delete-modal h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .delete-modal p {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 24px;
        }

        .delete-modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .delete-modal-cancel {
            padding: 10px 24px;
            background: #f3f4f6;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s;
        }

        .delete-modal-cancel:hover {
            background: #e5e7eb;
        }

        .delete-modal-confirm {
            padding: 10px 24px;
            background: #ef4444;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: white;
            cursor: pointer;
            transition: all 0.2s;
        }

        .delete-modal-confirm:hover {
            background: #dc2626;
        }

        .delete-modal-confirm:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .editing-container {
            width: 100%;
            animation: slideDown 0.2s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .edit-textarea {
            width: 100%;
            min-height: 80px;
            padding: 8px 12px;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            outline: none;
            background: #ffffff;
        }

        .edit-textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .edit-buttons {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            justify-content: flex-end;
        }

        .edit-save-btn {
            padding: 6px 16px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .edit-save-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .edit-save-btn:active {
            transform: translateY(0);
        }

        .edit-save-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .edit-cancel-btn {
            padding: 6px 16px;
            background: transparent;
            color: #6b7280;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .edit-cancel-btn:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .edit-loader {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 6px;
        }

        .edited-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: #9ca3af;
            font-style: italic;
            margin-left: 8px;
        }

        .edited-icon {
            font-size: 10px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Login Section -->
    <div id="loginSection" class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
            <h1 class="text-3xl font-bold text-center mb-6 text-blue-600">
                💬 Chat Service Test
            </h1>
            <form id="loginForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="loginEmail" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="user@example.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" id="loginPassword" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Device Name</label>
                    <input type="text" id="deviceName" value="web-test"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="submit"
                    class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    Se Connecter
                </button>
            </form>
            <div id="loginError" class="mt-4 p-4 bg-red-100 text-red-700 rounded-lg hidden"></div>
            <div id="loginSuccess" class="mt-4 p-4 bg-green-100 text-green-700 rounded-lg hidden"></div>
        </div>
    </div>

    <!-- Chat Section -->
    <div id="chatSection" class="hidden min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-md p-4">
            <div class="container mx-auto flex justify-between items-center">
                <h1 class="text-2xl font-bold text-blue-600">💬 Chat Service Test</h1>
                <div class="flex items-center gap-4">
                    <span id="connectionStatus" class="px-3 py-1 rounded-full text-sm bg-yellow-100 text-yellow-700">
                        ⚡ Déconnecté
                    </span>
                    <span id="userInfo" class="text-sm text-gray-600"></span>
                    <button id="logoutBtn"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Déconnexion
                    </button>
                </div>
            </div>
        </div>

        <div class="container mx-auto p-4 flex gap-4">
            <!-- Sidebar - Conversations -->
            <div class="w-1/3 bg-white rounded-lg shadow-md p-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">Conversations</h2>
                    <button id="createConversationBtn"
                        class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                        + Nouvelle
                    </button>
                </div>

                <!-- Users List for New Conversation -->
                <div id="usersList" class="mb-4 hidden">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Sélectionner un utilisateur:</h3>
                    <div id="usersListContent" class="space-y-2 max-h-48 overflow-y-auto"></div>
                    <button id="cancelNewConversation" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                        Annuler
                    </button>
                </div>

                <!-- Conversations List -->
                <div id="conversationsList" class="space-y-2 max-h-96 overflow-y-auto">
                    <p class="text-gray-500 text-center py-4">Chargement...</p>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="w-2/3 bg-white rounded-lg shadow-md flex flex-col">
                <!-- Conversation Header -->
                <div id="conversationHeader" class="p-4 border-b hidden">
                    <h3 id="conversationTitle" class="text-lg font-semibold"></h3>
                    <p id="conversationInfo" class="text-sm text-gray-600"></p>
                </div>

                <!-- Messages -->
                <div id="messagesContainer" class="messages-container p-4 space-y-3">
                    <div class="text-center text-gray-500 py-8">
                        Sélectionnez une conversation pour commencer
                    </div>
                </div>

                <!-- Typing Indicator -->
                <div id="typingIndicator" class="hidden px-4 pb-2">
                    <div class="typing-indicator-bubble">
                        <div class="typing-indicator">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>

                <!-- Message Input -->
                <div id="messageInput" class="p-4 border-t hidden">
                    <form id="messageForm" class="flex flex-col gap-3">
                        <!-- Prévisualisation de l'image -->
                        <div id="imagePreviewContainer" class="image-preview">
                            <img id="imagePreview" src="" alt="Prévisualisation">
                            <div id="uploadingOverlay" class="uploading-overlay">
                                <div class="uploading-spinner"></div>
                                <span class="text-sm text-gray-600">Envoi en cours...</span>
                            </div>
                            <button type="button" id="removeImagePreview" class="image-preview-remove">✕</button>
                        </div>

                        <!-- Prévisualisation de l'audio -->
                        <div id="audioPreviewContainer" class="audio-preview hidden">
                            <div class="audio-player-wrapper">
                                <audio id="audioPreview" controls></audio>
                                <div class="audio-info">
                                    <span id="audioDuration" class="audio-duration">0:00</span>
                                </div>
                            </div>
                            <button type="button" id="removeAudioPreview" class="audio-preview-remove">✕</button>
                        </div>

                        <!-- Overlay d'enregistrement audio -->
                        <div id="recordingOverlay" class="recording-overlay hidden">
                            <div class="recording-content">
                                <div class="recording-indicator">
                                    <span class="recording-dot"></span>
                                </div>
                                <h3 class="recording-title">Enregistrement en cours...</h3>
                                <p id="recordingTimer" class="recording-timer">0:00</p>
                                <div class="recording-buttons">
                                    <button type="button" id="stopRecordingBtn" class="recording-stop-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-6 h-6">
                                            <rect x="6" y="6" width="12" height="12" rx="2" />
                                        </svg>
                                        Stop
                                    </button>
                                    <button type="button" id="cancelRecordingBtn" class="recording-cancel-btn">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Zone de saisie et boutons -->
                        <div class="flex gap-2">
                            <!-- Bouton d'upload d'image -->
                            <div class="image-upload-container">
                                <input type="file" id="imageInput" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="hidden">
                                <button type="button" id="imageUploadButton" class="image-upload-button">
                                <svg xmlns="http://www.w3.org/2000/svg"
                             class="w-5 h-5 text-gray-600"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M21.44 11.05l-8.49 8.49a5.5 5.5 0 01-7.78-7.78l9.19-9.19a3.5 3.5 0 114.95 4.95l-9.2 9.19a1.5 1.5 0 11-2.12-2.12l8.49-8.49"/>
                        </svg>
                                </button>
                            </div>

                            <!-- Bouton d'enregistrement vocal -->
                            <button type="button" id="recordAudioButton" class="audio-record-button">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="w-5 h-5 text-gray-600"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor"
                                     stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                </svg>
                            </button>

                            <!-- Champ de saisie -->
                            <input type="text" id="messageContent"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Écrivez votre message...">

                            <!-- Bouton d'envoi -->
                            <button type="submit"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Envoyer
                            </button>
                        </div>

                        <!-- Message d'erreur de validation -->
                        <div id="validationError" class="validation-error"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteModalOverlay" class="delete-modal-overlay">
        <div class="delete-modal">
            <div class="delete-modal-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3>Supprimer ce message ?</h3>
            <p>Cette action est irréversible. Le message sera remplacé par "Message supprimé".</p>
            <div class="delete-modal-buttons">
                <button type="button" id="cancelDeleteBtn" class="delete-modal-cancel">Annuler</button>
                <button type="button" id="confirmDeleteBtn" class="delete-modal-confirm">Supprimer</button>
            </div>
        </div>
    </div>

    <!-- Configuration Modal -->
    <div id="configModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-xl font-bold mb-4">Configuration WebSocket</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reverb Key</label>
                    <input type="text" id="reverbKey" value="iuvcjjlml7xkwbdfaxo3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reverb Host</label>
                    <input type="text" id="reverbHost" value="localhost"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reverb Port</label>
                    <input type="number" id="reverbPort" value="8080"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Application ID</label>
                    <input type="text" id="appId" value="test-app-001"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">API Base URL</label>
                    <input type="text" id="apiBaseUrl" value="http://localhost:8000"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="mt-6 flex gap-2">
                <button id="saveConfig"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Sauvegarder
                </button>
                <button id="closeConfig"
                    class="flex-1 px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">
                    Fermer
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de visualisation d'image -->
    <div id="imageModal" class="image-modal">
        <button type="button" id="closeImageModal" class="image-modal-close">✕</button>
        <img id="modalImage" src="" alt="Image en plein écran">
    </div>

    <!-- Overlay de compression d'image -->
    <div id="compressionOverlay" class="compression-overlay">
        <div class="compression-modal">
            <div class="compression-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <h3 class="compression-title">Compression en cours...</h3>
            <p class="compression-subtitle">L'image dépasse 5 Mo et est automatiquement réduite</p>
            <div class="compression-progress">
                <div id="compressionProgressBar" class="compression-progress-bar"></div>
            </div>
            <p id="compressionStatus" class="compression-status">Analyse de l'image...</p>
            <div id="compressionInfo" class="compression-info" style="display: none;">
                <div class="compression-info-row">
                    <span class="compression-info-label">Taille originale :</span>
                    <span id="originalSize" class="compression-info-value">-</span>
                </div>
                <div class="compression-info-row">
                    <span class="compression-info-label">Nouvelle taille :</span>
                    <span id="compressedSize" class="compression-info-value success">-</span>
                </div>
                <div class="compression-info-row">
                    <span class="compression-info-label">Réduction :</span>
                    <span id="compressionRatio" class="compression-info-value success">-</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuration
        let config = {
            reverbKey: localStorage.getItem('reverbKey') || 'iuvcjjlml7xkwbdfaxo3',
            reverbHost: localStorage.getItem('reverbHost') || 'localhost',
            reverbPort: localStorage.getItem('reverbPort') || '8080',
            appId: localStorage.getItem('appId') || 'test-app-001',
            apiBaseUrl: localStorage.getItem('apiBaseUrl') || 'http://localhost:8000'
        };

        let token = localStorage.getItem('token');
        let currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
        let pusher = null;
        let currentConversation = null;
        let conversations = [];

        // Set pour suivre les messages déjà affichés (éviter les doublons)
        const displayedMessageIds = new Set();

        // Map pour suivre les messages déjà traités (éviter les incréments multiples)
        const processedMessageIds = new Map(); // Map: messageId → timestamp du dernier traitement

        // ✅ NOUVEAU : Set pour suivre les canaux WebSocket déjà abonnés (éviter les doublons)
        const subscribedChannels = new Set();

        // Flag pour éviter les incréments concurrents pendant le marquage comme lu
        let isMarkingAsRead = false;

        // ✅ NOUVEAU : Map pour stocker le dernier statut "lu" par conversation
        // conversationId → { readerId, readerName, readAt }
        const conversationReadStatus = new Map();

        // ✅ NOUVEAU : Intervalle pour mettre à jour l'affichage du temps "Vu il y a X min"
        let readStatusUpdateInterval = null;

        // ✅ NOUVEAU : Variables pour l'upload d'images
        let selectedImageFile = null;
        let isUploading = false;

        // ✅ NOUVEAU : Variables pour l'enregistrement audio
        let audioRecorder = null;
        let audioChunks = [];
        let recordedAudioBlob = null;
        let isRecording = false;
        let recordingStartTime = null;
        let recordingTimerInterval = null;
        let selectedAudioFile = null;
        let selectedAudioDuration = null;

        // ✅ NOUVEAU : Variables pour la compression d'images
        let isCompressing = false;
        const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 Mo en octets
        const COMPRESSION_QUALITY_START = 0.9; // Qualité initiale
        const COMPRESSION_QUALITY_MIN = 0.5; // Qualité minimale
        const MAX_DIMENSION = 2048; // Dimension max (largeur ou hauteur)

        // ✅ NOUVEAU : Variables pour la suppression de messages
        let messageToDelete = null; // ID du message à supprimer
        let isDeleting = false; // Flag pour éviter les suppressions multiples

        // ✅ NOUVEAU : Variables pour l'indicateur de frappe
        let isTyping = false; // Indique si l'utilisateur est en train de taper
        let typingTimeout = null; // Timeout pour masquer l'indicateur après inactivité
        let typingDebounce = null; // Debounce pour éviter d'envoyer trop d'événements

        // DOM Elements
        const loginSection = document.getElementById('loginSection');
        const chatSection = document.getElementById('chatSection');
        const loginForm = document.getElementById('loginForm');
        const loginError = document.getElementById('loginError');
        const loginSuccess = document.getElementById('loginSuccess');
        const logoutBtn = document.getElementById('logoutBtn');
        const createConversationBtn = document.getElementById('createConversationBtn');
        const usersList = document.getElementById('usersList');
        const usersListContent = document.getElementById('usersListContent');
        const conversationsList = document.getElementById('conversationsList');
        const conversationHeader = document.getElementById('conversationHeader');
        const conversationTitle = document.getElementById('conversationTitle');
        const conversationInfo = document.getElementById('conversationInfo');
        const messagesContainer = document.getElementById('messagesContainer');
        const messageInput = document.getElementById('messageInput');
        const messageForm = document.getElementById('messageForm');
        const messageContent = document.getElementById('messageContent');
        const connectionStatus = document.getElementById('connectionStatus');
        const userInfo = document.getElementById('userInfo');
        const typingIndicator = document.getElementById('typingIndicator');
        const cancelNewConversation = document.getElementById('cancelNewConversation');
        const configModal = document.getElementById('configModal');

        // ✅ NOUVEAU : Éléments pour l'upload d'images
        const imageInput = document.getElementById('imageInput');
        const imageUploadButton = document.getElementById('imageUploadButton');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
        const imagePreview = document.getElementById('imagePreview');
        const removeImagePreview = document.getElementById('removeImagePreview');

        // ✅ NOUVEAU : Éléments pour l'enregistrement audio
        const recordAudioButton = document.getElementById('recordAudioButton');
        const audioPreviewContainer = document.getElementById('audioPreviewContainer');
        const audioPreview = document.getElementById('audioPreview');
        const audioDuration = document.getElementById('audioDuration');
        const removeAudioPreview = document.getElementById('removeAudioPreview');
        const recordingOverlay = document.getElementById('recordingOverlay');
        const recordingTimer = document.getElementById('recordingTimer');
        const stopRecordingBtn = document.getElementById('stopRecordingBtn');
        const cancelRecordingBtn = document.getElementById('cancelRecordingBtn');
        const validationError = document.getElementById('validationError');
        const uploadingOverlay = document.getElementById('uploadingOverlay');

        // ✅ NOUVEAU : Éléments pour la suppression de messages
        const deleteModalOverlay = document.getElementById('deleteModalOverlay');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

        // ✅ NOUVEAU : Éléments pour la compression d'images
        const compressionOverlay = document.getElementById('compressionOverlay');
        const compressionProgressBar = document.getElementById('compressionProgressBar');
        const compressionStatus = document.getElementById('compressionStatus');
        const compressionInfo = document.getElementById('compressionInfo');
        const originalSizeEl = document.getElementById('originalSize');
        const compressedSizeEl = document.getElementById('compressedSize');
        const compressionRatioEl = document.getElementById('compressionRatio');
        const imageModal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const closeImageModal = document.getElementById('closeImageModal');

        // Initialize
        function init() {
            if (token && currentUser) {
                showChat();
            } else {
                showLogin();
            }

            loadConfig();
        }

        // Show/Hide Sections
        function showLogin() {
            loginSection.classList.remove('hidden');
            chatSection.classList.add('hidden');
        }

        function showChat() {
            loginSection.classList.add('hidden');
            chatSection.classList.remove('hidden');
            userInfo.textContent = `👤 ${currentUser.name}`;
            connectWebSocket();
            loadConversations();
        }

        // Login
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            const deviceName = document.getElementById('deviceName').value;

            try {
                const response = await fetch(`${config.apiBaseUrl}/api/auth/login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password, device_name: deviceName })
                });

                const data = await response.json();

                if (data.success) {
                    token = data.data.token;
                    currentUser = data.data.user;
                    localStorage.setItem('token', token);
                    localStorage.setItem('currentUser', JSON.stringify(currentUser));

                    loginSuccess.textContent = '✅ Connexion réussie !';
                    loginSuccess.classList.remove('hidden');
                    loginError.classList.add('hidden');

                    setTimeout(() => {
                        showChat();
                    }, 1000);
                } else {
                    loginError.textContent = `❌ ${data.message}`;
                    loginError.classList.remove('hidden');
                    loginSuccess.classList.add('hidden');
                }
            } catch (error) {
                loginError.textContent = `❌ Erreur de connexion: ${error.message}`;
                loginError.classList.remove('hidden');
                loginSuccess.classList.add('hidden');
            }
        });

        // Logout
        logoutBtn.addEventListener('click', async () => {
            try {
                await fetch(`${config.apiBaseUrl}/api/auth/logout`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });
            } catch (error) {
                console.error('Logout error:', error);
            }

            localStorage.removeItem('token');
            localStorage.removeItem('currentUser');
            token = null;
            currentUser = null;

            if (pusher) {
                pusher.disconnect();
                pusher = null;
            }

            location.reload();
        });

        // WebSocket Connection
        function connectWebSocket() {
            try {
                pusher = new Pusher(config.reverbKey, {
                    cluster: 'mt1',
                    wsHost: config.reverbHost,
                    wsPort: parseInt(config.reverbPort),
                    wssPort: parseInt(config.reverbPort),
                    forceTLS: false,
                    enabledTransports: ['ws', 'wss'],
                    authEndpoint: `${config.apiBaseUrl}/api/v1/broadcasting/auth`,
                    auth: {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'X-Application-ID': config.appId
                        }
                    }
                });

                pusher.connection.bind('connected', () => {
                    connectionStatus.textContent = '✅ Connecté';
                    connectionStatus.className = 'px-3 py-1 rounded-full text-sm bg-green-100 text-green-700';
                    console.log('✅ Connected to Reverb');
                });

                pusher.connection.bind('disconnected', () => {
                    connectionStatus.textContent = '⚡ Déconnecté';
                    connectionStatus.className = 'px-3 py-1 rounded-full text-sm bg-yellow-100 text-yellow-700';
                    console.log('⚡ Disconnected from Reverb');
                });

                pusher.connection.bind('error', (err) => {
                    connectionStatus.textContent = '❌ Erreur';
                    connectionStatus.className = 'px-3 py-1 rounded-full text-sm bg-red-100 text-red-700';
                    console.error('❌ Reverb error:', err);
                });

            } catch (error) {
                console.error('Failed to connect to Reverb:', error);
                connectionStatus.textContent = '❌ Erreur de connexion';
                connectionStatus.className = 'px-3 py-1 rounded-full text-sm bg-red-100 text-red-700';
            }
        }

        // Subscribe to conversation channel
        function subscribeToConversation(conversationId) {
            if (!pusher) {
                console.error('❌ [SUBSCRIBE] Pusher not connected!');
                return;
            }

            // Format correct pour Reverb: private-conversation.{id}.{appId}
            const channelName = `private-conversation.${conversationId}.${config.appId}`;
            
            // ✅ NOUVEAU : Vérifier si déjà abonné pour éviter les doublons
            if (subscribedChannels.has(channelName)) {
                console.log('ℹ️ [SUBSCRIBE] Already subscribed to:', channelName);
                return;
            }

            console.log('🔔 [SUBSCRIBE] Subscribing to channel:', channelName);
            console.log('🔔 [SUBSCRIBE] Pusher state:', pusher.connection.state);
            console.log('🔔 [SUBSCRIBE] Socket ID:', pusher.connection.socket_id);
            
            const channel = pusher.subscribe(channelName);

            // Marquer comme abonné
            subscribedChannels.add(channelName);

            // Bind ALL events pour déboguer
            channel.bind_global((eventName, data) => {
                console.log('🌐 [GLOBAL EVENT]', eventName, data);
            });

            // Écouter l'événement "message.sent" (nom défini dans MessageSent::broadcastAs())
            channel.bind('message.sent', (data) => {
                console.log('📨 [REALTIME] ========================================');
                console.log('📨 [REALTIME] Message received via WebSocket!');
                console.log('📨 [REALTIME] Data:', JSON.stringify(data, null, 2));
                console.log('📨 [REALTIME] Current conversation:', currentConversation?.id);
                console.log('📨 [REALTIME] Current user:', currentUser?.id);
                console.log('📨 [REALTIME] ========================================');

                // Add message to UI if it's the current conversation AND not from current user
                if (currentConversation && currentConversation.id === data.message.conversation_id) {
                    if (data.sender.id !== currentUser.id) {
                        console.log('✅ [REALTIME] Adding message to UI (from other user)');
                        appendMessage(data.message, data.sender, false);
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;

                        // ✅ AUTO-MARK AS READ : L'utilisateur est dans la conversation, donc il voit le message
                        // Cela va déclencher l'événement "message.read" vers l'expéditeur (User1)
                        console.log('👁️ [AUTO-READ] Marquage automatique comme lu (utilisateur dans la conversation)');
                        markAsRead(data.message.conversation_id);

                        // ✅ TYPING INDICATOR : Masquer l'indicateur lors de la réception d'un message
                        hideTypingIndicator();
                    } else {
                        console.log('ℹ️ [REALTIME] Skipping own message (already added via API response)');
                    }
                } else {
                    console.log('ℹ️ [REALTIME] Message for different conversation, not adding to UI');
                    // Ne PAS marquer comme lu car l'utilisateur n'est pas dans cette conversation
                }

                // Update conversation in list (mettre à jour le dernier message et le compteur)
                updateConversationInList(data.message.conversation_id, data.message, data.sender);
            });

            // ✅ Écouter l'événement "message.read" pour le statut "vu"
            channel.bind('message.read', (data) => {
                console.log('👁️ [READ EVENT] ========================================');
                console.log('👁️ [READ EVENT] Événement message.read reçu!');
                console.log('👁️ [READ EVENT] Full data:', JSON.stringify(data, null, 2));
                console.log('👁️ [READ EVENT] conversation_id:', data.conversation_id);
                console.log('👁️ [READ EVENT] reader.id:', data.reader?.id);
                console.log('👁️ [READ EVENT] reader.name:', data.reader?.name);
                console.log('👁️ [READ EVENT] read_at:', data.read_at);
                console.log('👁️ [READ EVENT] Current user ID:', currentUser?.id);
                console.log('👁️ [READ EVENT] Current conversation ID:', currentConversation?.id);
                console.log('👁️ [READ EVENT] ========================================');

                // Vérifier : est-ce que l'utilisateur courant est l'expéditeur des messages ?
                // (L'événement vient de l'autre utilisateur qui a lu)
                if (!data.reader || data.reader.id === currentUser.id) {
                    console.log('⏭️ [READ EVENT] Ignoré : current user read their own messages');
                    return;
                }

                console.log('✅ [READ EVENT] Traitement du statut "Vu"');

                // 1. TOUJOURS stocker le statut (même si conversation fermée)
                conversationReadStatus.set(data.conversation_id, {
                    readerId: data.reader.id,
                    readerName: data.reader.name,
                    readAt: data.read_at
                });
                console.log('💾 [READ EVENT] Statut stocké pour conversation', data.conversation_id);

                // 2. Afficher SEULEMENT si cette conversation est ouverte
                const isConversationOpen = currentConversation && currentConversation.id === data.conversation_id;
                console.log('📱 [READ EVENT] Conversation ouverte?', isConversationOpen);
                
                if (isConversationOpen) {
                    console.log('✅ [READ EVENT] Affichage immédiat (conversation ouverte)');
                    updateReadStatus(data.reader, data.read_at);
                } else {
                    console.log('⏸️ [READ EVENT] Statut stocké pour affichage ultérieur (conversation fermée)');
                }
            });

            // ✅ Écouter l'événement "message.deleted" pour la suppression en temps réel
            channel.bind('message.deleted', (data) => {
                console.log('🗑️ [DELETE EVENT] ========================================');
                console.log('🗑️ [DELETE EVENT] Événement message.deleted reçu!');
                console.log('🗑️ [DELETE EVENT] Full data:', JSON.stringify(data, null, 2));
                console.log('🗑️ [DELETE EVENT] message_id:', data.message_id);
                console.log('🗑️ [DELETE EVENT] conversation_id:', data.conversation_id);
                console.log('🗑️ [DELETE EVENT] deleted_by:', data.deleted_by?.id);
                console.log('🗑️ [DELETE EVENT] Current user ID:', currentUser?.id);
                console.log('🗑️ [DELETE EVENT] ========================================');

                // Ne pas traiter si c'est nous qui avons supprimé (déjà fait localement)
                if (data.deleted_by && data.deleted_by.id === currentUser.id) {
                    console.log('⏭️ [DELETE EVENT] Ignoré : suppression par l\'utilisateur courant');
                    return;
                }

                // Mettre à jour l'UI si la conversation est ouverte
                if (currentConversation && currentConversation.id === data.conversation_id) {
                    console.log('✅ [DELETE EVENT] Mise à jour de l\'UI pour le message supprimé');
                    markMessageAsDeleted(data.message_id);
                } else {
                    console.log('ℹ️ [DELETE EVENT] Message dans une autre conversation, pas de mise à jour UI');
                }
            });

            // ✅ Écouter l'événement "user.typing" pour l'indicateur de frappe
            channel.bind('user.typing', (data) => {
                console.log('⌨️ [TYPING EVENT] ========================================');
                console.log('⌨️ [TYPING EVENT] Événement user.typing reçu!');
                console.log('⌨️ [TYPING EVENT] Full data:', JSON.stringify(data, null, 2));
                console.log('⌨️ [TYPING EVENT] user.id:', data.user?.id);
                console.log('⌨️ [TYPING EVENT] is_typing:', data.is_typing);
                console.log('⌨️ [TYPING EVENT] Current user ID:', currentUser?.id);
                console.log('⌨️ [TYPING EVENT] ========================================');

                // Ne pas afficher l'indicateur si c'est l'utilisateur courant qui tape
                if (data.user && data.user.id === currentUser.id) {
                    console.log('⏭️ [TYPING EVENT] Ignoré : current user is typing (don\'t show own indicator)');
                    return;
                }

                // Afficher ou masquer l'indicateur selon l'état
                if (data.is_typing) {
                    console.log('✅ [TYPING EVENT] Affichage de l\'indicateur de frappe');
                    showTypingIndicator();
                } else {
                    console.log('⏹️ [TYPING EVENT] Masquage de l\'indicateur de frappe');
                    hideTypingIndicator();
                }
            });

            channel.bind('pusher:subscription_succeeded', () => {
                console.log('✅ [SUBSCRIBE] Successfully subscribed to:', channelName);
                console.log('✅ [SUBSCRIBE] Channel members:', channel.members);
            });

            channel.bind('pusher:subscription_error', (error) => {
                console.error('❌ [SUBSCRIBE] Subscription error for', channelName, ':', error);
                // Retirer du Set si l'abonnement échoue
                subscribedChannels.delete(channelName);
            });
        }

        // ✅ NOUVEAU : S'abonner à TOUTES les conversations pour recevoir les messages en temps réel
        function subscribeToAllConversations() {
            if (!pusher || pusher.connection.state !== 'connected') {
                console.log('⏳ [SUBSCRIBE ALL] Waiting for Pusher connection...');
                // Réessayer après 1 seconde si pas encore connecté
                setTimeout(subscribeToAllConversations, 1000);
                return;
            }

            console.log('🔔 [SUBSCRIBE ALL] Subscribing to all', conversations.length, 'conversations');
            
            conversations.forEach(conv => {
                subscribeToConversation(conv.id);
            });

            console.log('✅ [SUBSCRIBE ALL] Subscribed to', subscribedChannels.size, 'channels');
        }

        // Load Conversations
        async function loadConversations() {
            try {
                console.log('=== Chargement des conversations ===');
                console.log('URL:', `${config.apiBaseUrl}/api/v1/conversations`);
                console.log('Token:', token ? `${token.substring(0, 20)}...` : 'NON');
                console.log('App ID:', config.appId);

                const response = await fetch(`${config.apiBaseUrl}/api/v1/conversations`, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-Application-ID': config.appId
                    }
                });

                console.log('Response status:', response.status, response.statusText);
                console.log('Response headers:', Object.fromEntries(response.headers.entries()));

                const data = await response.json();
                console.log('Response data:', data);

                if (data.success) {
                    conversations = data.data;
                    console.log('Conversations chargées:', conversations.length);
                    renderConversations();
                    
                    // ✅ NOUVEAU : S'abonner à TOUTES les conversations pour recevoir les badges en temps réel
                    subscribeToAllConversations();
                } else {
                    console.error('API returned error:', data.message);
                    conversationsList.innerHTML = `<p class="text-red-500 text-center py-4">${data.message}</p>`;
                }
            } catch (error) {
                console.error('Failed to load conversations:', error);
                console.error('Error details:', {
                    message: error.message,
                    name: error.name,
                    stack: error.stack
                });
                conversationsList.innerHTML = `<p class="text-red-500 text-center py-4">Erreur de chargement: ${error.message}</p>
                    <p class="text-xs text-gray-500 mt-2">Vérifiez la console (F12) pour plus de détails</p>`;
            }
        }

        // Render Conversations
        function renderConversations() {
            if (conversations.length === 0) {
                conversationsList.innerHTML = `<p class="text-gray-500 text-center py-4">Aucune conversation</p>`;
                return;
            }

            conversationsList.innerHTML = conversations.map(conv => {
                const isActive = currentConversation && currentConversation.id === conv.id;

                // Badge moderne avec gestion des états
                let unreadBadge = '';
                if (conv.unread_count > 0) {
                    const displayCount = conv.unread_count > 9 ? '9+' : conv.unread_count;
                    const badgeSize = conv.unread_count > 9 ? 'badge-large' : 'badge-normal';

                    // Ajouter l'attribut data-badge-id pour l'animation de mise à jour
                    const badgeId = `badge-${conv.id}`;
                    unreadBadge = `
                        <span id="${badgeId}" class="badge ${badgeSize} badge-pulse">
                            ${displayCount}
                        </span>
                    `;
                }

                const lastMessage = conv.last_message
                    ? `<p class="text-sm text-gray-500 truncate">${conv.last_message.user?.name || 'Unknown'}: ${conv.last_message.content}</p>`
                    : '<p class="text-sm text-gray-500">Pas de messages</p>';

                return `
                    <div onclick="selectConversation(${conv.id})"
                        class="conversation-item p-3 rounded-lg cursor-pointer transition-colors ${isActive ? 'bg-blue-100' : 'hover:bg-gray-100'}">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h4 class="font-medium flex items-center">
                                    ${conv.display_name || 'Conversation'}
                                    ${unreadBadge}
                                </h4>
                                ${lastMessage}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Select Conversation
        async function selectConversation(conversationId) {
            currentConversation = conversations.find(c => c.id === conversationId);
            renderConversations();

            // ✅ TYPING INDICATOR : Masquer l'indicateur lors du changement de conversation
            hideTypingIndicator();

            // Subscribe to WebSocket channel
            subscribeToConversation(conversationId);

            // Load messages
            await loadMessages(conversationId);

            // ✅ Vérifier si un statut "Vu" existe déjà (reçu via WebSocket avant l'ouverture)
            checkAndApplySeenStatus(conversationId);

            // Mark as read SEULEMENT si des messages ont été chargés et affichés
            const messagesLoaded = document.querySelectorAll('#messagesContainer > div');
            if (messagesLoaded.length > 0) {
                await markAsRead(conversationId);
            }

            // Update UI
            conversationHeader.classList.remove('hidden');
            messageInput.classList.remove('hidden');
            conversationTitle.textContent = currentConversation.display_name || currentConversation.name || 'Conversation';
            conversationInfo.textContent = currentConversation.type === 'group'
                ? `Groupe • ${currentConversation.participants_count} membres`
                : 'Conversation directe';
        }

        /**
         * Vérifie si un statut "Vu" a déjà été stocké via WebSocket
         * (reçu alors que la conversation n'était pas ouverte)
         * Si oui, l'affiche immédiatement
         */
        function checkAndApplySeenStatus(conversationId) {
            const status = conversationReadStatus.get(conversationId);
            
            if (status) {
                console.log('👁️ [VU] Statut existant trouvé, affichage immédiat:', status);
                
                // Afficher le statut "Vu" existant
                const seenText = getSeenText(status.readAt);
                
                // Trouver le DERNIER message envoyé
                const allMessages = messagesContainer.querySelectorAll('[data-message-id]');
                let lastSentMessageId = null;
                
                allMessages.forEach(messageDiv => {
                    const messageId = messageDiv.dataset.messageId;
                    const readStatusEl = document.getElementById(`read-status-${messageId}`);
                    if (readStatusEl) {
                        // Effacer le statut des messages précédents
                        readStatusEl.textContent = '';
                        readStatusEl.className = 'text-xs ml-2';
                        lastSentMessageId = messageId;
                    }
                });

                // Afficher le statut sur le dernier message
                if (lastSentMessageId) {
                    const readStatusEl = document.getElementById(`read-status-${lastSentMessageId}`);
                    if (readStatusEl) {
                        readStatusEl.textContent = seenText;
                        readStatusEl.className = 'text-xs ml-2 text-blue-500 font-medium seen-status seen-status-animated';
                        
                        // Retirer la classe d'animation après qu'elle soit terminée
                        setTimeout(() => {
                            readStatusEl.classList.remove('seen-status-animated');
                        }, 400);
                        
                        console.log('✅ [VU] Statut existant affiché:', seenText);
                    }
                }
                
                // Démarrer l'intervalle de mise à jour automatique
                startSeenInterval();
            } else {
                console.log('ℹ️ [VU] Pas de statut existant pour cette conversation');
            }
        }

        // Load Messages
        async function loadMessages(conversationId) {
            try {
                const response = await fetch(
                    `${config.apiBaseUrl}/api/v1/conversations/${conversationId}/messages?per_page=50`,
                    {
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                            'X-Application-ID': config.appId
                        }
                    }
                );

                const data = await response.json();

                if (data.success) {
                    // Vider le Set de messages affichés (nouvelle conversation)
                    displayedMessageIds.clear();

                    // Vider la Map de messages traités (éviter les incréments multiples)
                    processedMessageIds.clear();

                    messagesContainer.innerHTML = '';
                    data.data.data.reverse().forEach(msg => {
                        const isSent = msg.user_id === currentUser.id;
                        appendMessage(msg, msg.user, isSent);
                    });
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            } catch (error) {
                console.error('Failed to load messages:', error);
            }
        }

        // Append Message to UI
        function appendMessage(message, user, isSent) {
            // Vérifier si le message est déjà affiché (éviter les doublons)
            if (displayedMessageIds.has(message.id)) {
                console.log('⏭️ [APPEND] Message already displayed, skipping:', message.id);
                return;
            }

            // Marquer le message comme affiché
            displayedMessageIds.add(message.id);

            const messageDiv = document.createElement('div');
            messageDiv.className = `flex ${isSent ? 'justify-end' : 'justify-start'}`;
            messageDiv.dataset.messageId = message.id;
            messageDiv.dataset.messageType = message.type;
            messageDiv.dataset.messageFileUrl = message.file_url || '';
            messageDiv.dataset.isSent = isSent ? 'true' : 'false';
            messageDiv.dataset.isDeleted = message.is_deleted ? 'true' : 'false';

            // Vérifier si le message est supprimé
            const isDeleted = message.is_deleted === true;

            // Construire le contenu du message
            let messageBody = '';

            if (isDeleted) {
                // Message supprimé : afficher l'indicateur
                messageBody = `
                    <div class="message-deleted-content">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        <span>Message supprimé</span>
                    </div>
                `;
            } else {
                // Si le message contient une image, l'afficher
                if (message.type === 'image' && message.file_url) {
                    messageBody += `<img src="${message.file_url}" alt="${message.file_name || 'Image'}" class="message-image" onclick="window.open('${message.file_url}', '_blank')">`;
                }

                // Si le message contient un audio, l'afficher
                if (message.type === 'audio' && message.file_url) {
                    const duration = message.duration ? formatDuration(message.duration) : '0:00';
                    messageBody += `
                        <div class="audio-message">
                            <audio controls src="${message.file_url}" class="message-audio">
                                Votre navigateur ne supporte pas l'élément audio.
                            </audio>
                            
                        </div>
                    `;
                }

                // Si le message contient du texte, l'afficher
                if (message.content) {
                    messageBody += `<p class="text-gray-800 ${messageBody ? 'mt-2' : ''}" data-content="true">${message.content}</p>`;
                }
            }

            // Vérifier si le message est édité
            const isEdited = !isDeleted && (message.is_edited || (message.edited_at && new Date(message.edited_at) > new Date(message.created_at)));

            // Boutons d'édition et de suppression uniquement pour les propres messages non supprimés
            // Les messages vocaux (type 'audio') ne peuvent PAS être modifiés
            const canEdit = isSent && !isDeleted && message.type !== 'audio';
            const editButton = canEdit ? `
                <button type="button" class="edit-button" onclick="startEditing(${message.id})" title="Éditer le message">
                    ✏️
                </button>
            ` : '';

            const deleteButton = (isSent && !isDeleted) ? `
                <button type="button" class="delete-button" onclick="confirmDeleteMessage(${message.id})" title="Supprimer le message">
                    🗑️
                </button>
            ` : '';

            // Classe CSS supplémentaire pour les messages supprimés
            const deletedClass = isDeleted ? 'message-deleted' : '';

            // PAS de statut "Vu" par défaut - il apparaîtra uniquement via WebSocket
            const messageContentHtml = `
                <div class="max-w-[70%] ${isSent ? 'message-sent' : 'message-received'} ${deletedClass} px-4 py-2 rounded-lg shadow message-bubble"
                     data-message-id="${message.id}"
                     ondblclick="${canEdit ? `startEditing(${message.id})` : ''}">
                    <div class="flex items-center justify-between">
                        <p class="font-medium text-sm text-gray-600 mb-1">${user?.name || 'Unknown'}</p>
                        <div class="flex items-center">
                            ${editButton}
                            ${deleteButton}
                        </div>
                    </div>
                    <div id="message-body-${message.id}" data-original-content="${message.content || ''}">
                        ${messageBody}
                    </div>
                   ${isEdited ? '<span class="edited-indicator"><span class="edited-icon">✎</span>Modifié</span>' : ''}
                    <div class="flex items-center justify-between mt-1">
                        <p class="text-xs text-gray-400">${new Date(message.created_at).toLocaleTimeString()}</p>
                        ${isSent ? `<span id="read-status-${message.id}" class="text-xs ml-2"></span>` : ''}
                    </div>
                </div>
            `;

            messageDiv.innerHTML = messageContentHtml;
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // =====================================================
        // STATUT "VU" - COMPORTEMENT WHATSAPP EXACT
        // =====================================================
        
        /**
         * Calcule le texte du statut "Vu"
         * 
         * RÈGLES PRÉCISES :
         * - 0-59 secondes  → "Vu"
         * - 60-119 sec     → "1 min"
         * - 120-179 sec    → "2 min"
         * - etc.
         * - JAMAIS "0 min"
         * - Le compteur continue indéfiniment
         */
        function getSeenText(readAt) {
            const readTime = new Date(readAt);
            const now = new Date();
            const totalSeconds = Math.floor((now - readTime) / 1000);
            const totalMinutes = Math.floor(totalSeconds / 60);

            // 0-59 secondes → "Vu" (JAMAIS "0 min")
            if (totalMinutes < 1) {
                return "Vu";
            }
            
            // 1+ minutes → "X min"
            return `${totalMinutes} min`;
        }

        /**
         * Met à jour l'affichage du temps sur le dernier message
         * Appelé toutes les 60 secondes par l'intervalle
         * 
         * Transitions :
         * - "Vu" → "1 min" (après 60s)
         * - "1 min" → "2 min" (après 120s)
         * - etc.
         */
        function refreshSeenStatus() {
            if (!currentConversation) return;
            
            const status = conversationReadStatus.get(currentConversation.id);
            if (!status) return;

            const seenText = getSeenText(status.readAt);
            
            // Trouver le DERNIER message envoyé par l'utilisateur courant
            const allMessages = messagesContainer.querySelectorAll('[data-message-id]');
            let lastSentMessageId = null;
            
            allMessages.forEach(messageDiv => {
                const readStatusEl = document.getElementById(`read-status-${messageDiv.dataset.messageId}`);
                if (readStatusEl) {
                    lastSentMessageId = messageDiv.dataset.messageId;
                }
            });

            // Mettre à jour UNIQUEMENT le dernier message (si statut déjà affiché)
            if (lastSentMessageId) {
                const readStatusEl = document.getElementById(`read-status-${lastSentMessageId}`);
                if (readStatusEl && readStatusEl.textContent !== '') {
                    readStatusEl.textContent = seenText;
                    console.log('⏱️ [VU] Mise à jour automatique:', seenText);
                }
            }
        }

        /**
         * Démarre l'intervalle de mise à jour du compteur
         * Se déclenche toutes les 60 secondes
         * Le compteur continue indéfiniment
         */
        function startSeenInterval() {
            if (readStatusUpdateInterval) {
                clearInterval(readStatusUpdateInterval);
            }
            // Mise à jour toutes les 60 secondes
            readStatusUpdateInterval = setInterval(refreshSeenStatus, 60000);
            console.log('⏱️ [VU] Intervalle démarré (60s)');
        }

        /**
         * Efface le statut "Vu" de tous les messages
         * Appelé quand l'utilisateur envoie un nouveau message
         * (car le nouveau message n'a pas encore été lu)
         */
        function clearAllSeenStatus() {
            if (!currentConversation) return;
            
            // Effacer le statut stocké
            conversationReadStatus.delete(currentConversation.id);
            
            // Arrêter l'intervalle de mise à jour
            if (readStatusUpdateInterval) {
                clearInterval(readStatusUpdateInterval);
                readStatusUpdateInterval = null;
            }
            
            // Effacer visuellement tous les statuts
            const allMessages = messagesContainer.querySelectorAll('[data-message-id]');
            allMessages.forEach(messageDiv => {
                const readStatusEl = document.getElementById(`read-status-${messageDiv.dataset.messageId}`);
                if (readStatusEl) {
                    readStatusEl.textContent = '';
                    readStatusEl.className = 'text-xs ml-2';
                }
            });
            
            console.log('🧹 [VU] Statuts effacés (nouveau message envoyé)');
        }

        /**
         * Appelé quand l'événement "message.read" est reçu via WebSocket
         * 
         * COMPORTEMENT :
         * 1. Affiche immédiatement "Vu" sur le dernier message
         * 2. Démarre l'intervalle pour "1 min", "2 min", etc.
         * 
         * @param {Object} reader - L'utilisateur qui a lu (id, name)
         * @param {string} readAt - Timestamp ISO de la lecture
         */
        function updateReadStatus(reader, readAt) {
            if (!currentConversation) return;
            
            console.log('👁️ [VU] Événement reçu:', {
                reader: reader.name,
                readAt: readAt,
                conversationId: currentConversation.id
            });

            // Stocker le statut pour cette conversation (pour les mises à jour futures)
            conversationReadStatus.set(currentConversation.id, {
                readerId: reader.id,
                readerName: reader.name,
                readAt: readAt
            });

            // Afficher "Vu" immédiatement (sera mis à jour après 60s)
            const seenText = getSeenText(readAt);
            
            // Trouver tous les messages envoyés par l'utilisateur courant
            const allMessages = messagesContainer.querySelectorAll('[data-message-id]');
            let lastSentMessageId = null;
            
            allMessages.forEach(messageDiv => {
                const messageId = messageDiv.dataset.messageId;
                const readStatusEl = document.getElementById(`read-status-${messageId}`);
                if (readStatusEl) {
                    // Effacer le statut des messages précédents
                    readStatusEl.textContent = '';
                    readStatusEl.className = 'text-xs ml-2';
                    lastSentMessageId = messageId;
                }
            });

            // Afficher "Vu" UNIQUEMENT sur le DERNIER message
            if (lastSentMessageId) {
                const readStatusEl = document.getElementById(`read-status-${lastSentMessageId}`);
                if (readStatusEl) {
                    readStatusEl.textContent = seenText;
                    readStatusEl.className = 'text-xs ml-2 text-blue-500 font-medium seen-status seen-status-animated';
                    
                    // Retirer la classe d'animation après qu'elle soit terminée
                    setTimeout(() => {
                        readStatusEl.classList.remove('seen-status-animated');
                    }, 400);
                    
                    console.log('✅ [VU] Affiché:', seenText);
                }
            }

            // Démarrer l'intervalle pour les mises à jour automatiques
            // "Vu" → "1 min" après 60s, puis "2 min", "3 min", etc.
            startSeenInterval();
        }

        // =====================================================
        // VALIDATION DU CONTENU (liens et numéros de téléphone)
        // =====================================================

        /**
         * Valide que le contenu ne contient pas de liens
         */
        function containsLinks(content) {
            // Pattern pour détecter les liens (http://, https://, www., domaines)
            const linkPatterns = [
                // Protocoles http/https
                /https?:\/\/[^\s<>"{}|\\^`\[\]]+/i,
                // www. sans protocole
                /www\.[^\s<>"{}|\\^`\[\]]+\.[^\s<>"{}|\\^`\[\]]+/i,
                // Domaines (ex: example.com, example.org)
                /[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}(:[0-9]{1,5})?(\/.*)?/i,
            ];

            for (const pattern of linkPatterns) {
                if (pattern.test(content)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Valide que le contenu ne contient pas de numéros de téléphone
         */
        function containsPhoneNumbers(content) {
            // Patterns pour détecter les numéros de téléphone
            const phonePatterns = [
                // Format international: +33 6 12 34 56 78
                /\+?\d{1,3}[\s\-\.\(\)]*\d{3}[\s\-\.\(\)]*\d{3}[\s\-\.\(\)]*\d{2}[\s\-\.\(\)]*\d{2}/,
                // Format français sans indicatif: 06 12 34 56 78 ou 0612345678
                /0[1-9](?:[\s\-\.\.]?\d{2}){4}/,
                // Format US/UK: (555) 123-4567
                /\(\d{3}\)\s*\d{3}[-\s]\d{4}/,
                // Format simple: 10 chiffres consécutifs
                /(?<!\d)\d{10}(?!\d)/,
                // Format avec espaces: 6 12 34 56 78
                /\d(?:[\s\-\.\.]?\d){9}/,
            ];

            for (const pattern of phonePatterns) {
                if (pattern.test(content)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Affiche un message d'erreur de validation
         */
        function showValidationError(message) {
            validationError.textContent = '❌ ' + message;
            validationError.classList.add('visible');
            setTimeout(() => {
                validationError.classList.remove('visible');
            }, 5000);
        }

        /**
         * Cache le message d'erreur de validation
         */
        function hideValidationError() {
            validationError.classList.remove('visible');
        }

        // =====================================================
        // COMPRESSION AUTOMATIQUE D'IMAGES (> 5 Mo)
        // =====================================================

        /**
         * Formate une taille de fichier en chaîne lisible
         */
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 octets';
            const k = 1024;
            const sizes = ['octets', 'Ko', 'Mo', 'Go'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        /**
         * Affiche l'overlay de compression
         */
        function showCompressionOverlay() {
            isCompressing = true;
            compressionOverlay.classList.add('visible');
            compressionProgressBar.style.width = '0%';
            compressionStatus.textContent = 'Analyse de l\'image...';
            compressionInfo.style.display = 'none';
        }

        /**
         * Cache l'overlay de compression
         */
        function hideCompressionOverlay() {
            isCompressing = false;
            compressionOverlay.classList.remove('visible');
        }

        /**
         * Met à jour l'état de la compression
         */
        function updateCompressionProgress(progress, status) {
            compressionProgressBar.style.width = progress + '%';
            compressionStatus.textContent = status;
        }

        /**
         * Affiche les informations de compression finale
         */
        function showCompressionResult(originalSize, compressedSize) {
            const reduction = ((originalSize - compressedSize) / originalSize * 100).toFixed(1);
            originalSizeEl.textContent = formatFileSize(originalSize);
            compressedSizeEl.textContent = formatFileSize(compressedSize);
            compressionRatioEl.textContent = `-${reduction}%`;
            compressionInfo.style.display = 'block';
        }

        /**
         * Compresse une image en utilisant Canvas API
         * @param {File} file - Le fichier image à compresser
         * @returns {Promise<File>} - Le fichier compressé
         */
        async function compressImage(file) {
            return new Promise((resolve, reject) => {
                const originalSize = file.size;
                console.log(`🖼️ [COMPRESSION] Début de la compression - Taille originale: ${formatFileSize(originalSize)}`);

                showCompressionOverlay();
                updateCompressionProgress(10, 'Chargement de l\'image...');

                const reader = new FileReader();

                reader.onload = (e) => {
                    updateCompressionProgress(20, 'Décodage de l\'image...');

                    const img = new Image();

                    img.onload = () => {
                        updateCompressionProgress(30, 'Analyse des dimensions...');

                        // Calculer les nouvelles dimensions
                        let { width, height } = img;
                        console.log(`🖼️ [COMPRESSION] Dimensions originales: ${width}x${height}`);

                        // Réduire les dimensions si nécessaire
                        if (width > MAX_DIMENSION || height > MAX_DIMENSION) {
                            if (width > height) {
                                height = Math.round((height * MAX_DIMENSION) / width);
                                width = MAX_DIMENSION;
                            } else {
                                width = Math.round((width * MAX_DIMENSION) / height);
                                height = MAX_DIMENSION;
                            }
                            console.log(`🖼️ [COMPRESSION] Nouvelles dimensions: ${width}x${height}`);
                        }

                        updateCompressionProgress(40, 'Création du canvas...');

                        // Créer le canvas
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');

                        // Dessiner l'image redimensionnée
                        ctx.drawImage(img, 0, 0, width, height);

                        updateCompressionProgress(50, 'Compression en cours...');

                        // Déterminer le format de sortie (WebP si supporté, sinon JPEG)
                        const outputFormat = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
                        let quality = COMPRESSION_QUALITY_START;
                        let attempts = 0;
                        const maxAttempts = 10;

                        /**
                         * Fonction récursive pour trouver la qualité optimale
                         */
                        function tryCompression() {
                            attempts++;
                            const progress = 50 + Math.min(40, attempts * 4);
                            updateCompressionProgress(progress, `Optimisation (tentative ${attempts})...`);

                            canvas.toBlob((blob) => {
                                if (!blob) {
                                    reject(new Error('Erreur lors de la création du blob'));
                                    hideCompressionOverlay();
                                    return;
                                }

                                console.log(`🖼️ [COMPRESSION] Tentative ${attempts}: qualité=${quality.toFixed(2)}, taille=${formatFileSize(blob.size)}`);

                                // Vérifier si la taille est acceptable
                                if (blob.size <= MAX_FILE_SIZE || quality <= COMPRESSION_QUALITY_MIN || attempts >= maxAttempts) {
                                    updateCompressionProgress(95, 'Finalisation...');

                                    // Créer un nouveau File à partir du Blob
                                    const compressedFileName = file.name.replace(/\.[^.]+$/, '') + '_compressed' + (outputFormat === 'image/png' ? '.png' : '.jpg');
                                    const compressedFile = new File([blob], compressedFileName, {
                                        type: outputFormat,
                                        lastModified: Date.now()
                                    });

                                    console.log(`✅ [COMPRESSION] Terminé! Taille finale: ${formatFileSize(compressedFile.size)} (réduction: ${((originalSize - compressedFile.size) / originalSize * 100).toFixed(1)}%)`);

                                    updateCompressionProgress(100, 'Compression terminée!');
                                    showCompressionResult(originalSize, compressedFile.size);

                                    // Attendre un moment pour montrer le résultat
                                    setTimeout(() => {
                                        hideCompressionOverlay();
                                        resolve(compressedFile);
                                    }, 1500);
                                } else {
                                    // Réduire la qualité et réessayer
                                    quality -= 0.05;
                                    tryCompression();
                                }
                            }, outputFormat, quality);
                        }

                        tryCompression();
                    };

                    img.onerror = () => {
                        reject(new Error('Erreur lors du chargement de l\'image'));
                        hideCompressionOverlay();
                    };

                    img.src = e.target.result;
                };

                reader.onerror = () => {
                    reject(new Error('Erreur lors de la lecture du fichier'));
                    hideCompressionOverlay();
                };

                reader.readAsDataURL(file);
            });
        }

        // =====================================================
        // GESTION DE L'UPLOAD D'IMAGES
        // =====================================================

        /**
         * Gère le clic sur le bouton d'upload d'image
         */
        imageUploadButton.addEventListener('click', () => {
            if (isUploading) {
                return;
            }
            imageInput.click();
        });

        /**
         * Gère la sélection d'un fichier image
         */
        imageInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];

            if (!file) {
                return;
            }

            // Valider le type de fichier
            if (!file.type.match(/image\/(jpeg|jpg|png|gif|webp)/)) {
                showValidationError('Le fichier doit être une image (JPEG, PNG, GIF ou WebP)');
                imageInput.value = '';
                return;
            }

            // Si le fichier dépasse 5Mo, compresser automatiquement
            if (file.size > MAX_FILE_SIZE) {
                console.log(`📦 [IMAGE] Fichier trop volumineux (${formatFileSize(file.size)}), compression automatique...`);

                try {
                    // Compresser l'image
                    const compressedFile = await compressImage(file);

                    // Vérifier que la compression a réussi
                    if (compressedFile.size > MAX_FILE_SIZE) {
                        showValidationError(`Impossible de compresser l'image en dessous de 5 Mo. Veuillez choisir une image plus petite.`);
                        imageInput.value = '';
                        return;
                    }

                    // Stocker le fichier compressé et afficher la prévisualisation
                    selectedImageFile = compressedFile;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        imagePreview.src = e.target.result;
                        imagePreviewContainer.classList.add('visible');
                    };
                    reader.readAsDataURL(compressedFile);

                    console.log(`✅ [IMAGE] Image compressée avec succès: ${formatFileSize(compressedFile.size)}`);

                } catch (error) {
                    console.error('❌ [IMAGE] Erreur de compression:', error);
                    showValidationError('Erreur lors de la compression de l\'image. Veuillez réessayer.');
                    imageInput.value = '';
                    return;
                }
            } else {
                // Fichier < 5Mo, stocker directement et afficher la prévisualisation
                console.log(`📦 [IMAGE] Fichier OK (${formatFileSize(file.size)}), pas de compression nécessaire`);
                selectedImageFile = file;
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.classList.add('visible');
                };
                reader.readAsDataURL(file);
            }
        });

        /**
         * Gère la suppression de l'image sélectionnée
         */
        removeImagePreview.addEventListener('click', () => {
            selectedImageFile = null;
            imageInput.value = '';
            imagePreview.src = '';
            imagePreviewContainer.classList.remove('visible');
            hideValidationError();
        });

        /**
         * Gère l'ouverture du modal de visualisation d'image
         */
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('message-image')) {
                modalImage.src = e.target.src;
                imageModal.classList.add('visible');
            }
        });

        /**
         * Gère la fermeture du modal de visualisation d'image
         */
        closeImageModal.addEventListener('click', () => {
            imageModal.classList.remove('visible');
        });

        imageModal.addEventListener('click', (e) => {
            if (e.target === imageModal) {
                imageModal.classList.remove('visible');
            }
        });

        // =====================================================
        // GESTION DE L'ENREGISTREMENT AUDIO
        // =====================================================

        /**
         * Formate la durée en secondes en format MM:SS
         * @param {number} seconds - Durée en secondes
         * @returns {string} Durée formatée
         */
        function formatDuration(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }

        /**
         * Gère le clic sur le bouton d'enregistrement audio
         */
        recordAudioButton.addEventListener('click', async () => {
            if (isRecording) {
                // Si déjà en enregistrement, arrêter
                stopRecording();
                return;
            }

            try {
                // Demander la permission d'accès au microphone
                console.log('🎙️ [AUDIO] Demande d\'accès au microphone...');
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

                console.log('🎙️ [AUDIO] Microphone autorisé, début de l\'enregistrement');

                // Démarrer l'enregistrement
                audioChunks = [];
                const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
                    ? 'audio/webm;codecs=opus'
                    : 'audio/mp4';
                audioRecorder = new MediaRecorder(stream, { mimeType });

                audioRecorder.ondataavailable = (event) => {
                    if (event.data.size > 0) {
                        audioChunks.push(event.data);
                    }
                };

                audioRecorder.onstop = () => {
                    const blob = new Blob(audioChunks, { type: audioRecorder.mimeType });
                    recordedAudioBlob = blob;
                    
                    // Calculer la durée de l'enregistrement
                    const duration = recordingStartTime 
                        ? (Date.now() - recordingStartTime) / 1000 
                        : 0;
                    selectedAudioDuration = Math.round(duration);

                    console.log('🎙️ [AUDIO] Enregistrement terminé:', {
                        size: formatFileSize(blob.size),
                        duration: formatDuration(selectedAudioDuration),
                        mimeType: blob.type
                    });

                    // Afficher la prévisualisation
                    const audioUrl = URL.createObjectURL(blob);
                    audioPreview.src = audioUrl;
                    audioDuration.textContent = formatDuration(selectedAudioDuration);
                    audioPreviewContainer.classList.add('visible');

                    // Convertir le Blob en File pour l'upload
                    const extension = mimeType.includes('webm') ? 'webm' : 'm4a';
                    selectedAudioFile = new File([blob], `voice-message-${Date.now()}.${extension}`, {
                        type: mimeType,
                        lastModified: Date.now()
                    });

                    // Arrêter le stream pour libérer le microphone
                    stream.getTracks().forEach(track => track.stop());

                    // Masquer l'overlay d'enregistrement
                    hideRecordingOverlay();
                };

                // Démarrer l'enregistrement
                audioRecorder.start();
                isRecording = true;
                recordingStartTime = Date.now();

                // Afficher l'overlay d'enregistrement
                showRecordingOverlay();

                // Démarrer le timer
                startRecordingTimer();

                // Mettre à jour le bouton
                recordAudioButton.classList.add('recording');

            } catch (error) {
                console.error('❌ [AUDIO] Erreur d\'accès au microphone:', error);

                if (error.name === 'NotAllowedError') {
                    alert('Accès au microphone refusé. Veuillez autoriser l\'accès pour enregistrer des messages vocaux.');
                } else if (error.name === 'NotFoundError') {
                    alert('Aucun microphone détecté. Veuillez vérifier votre périphérique.');
                } else {
                    alert('Erreur lors de l\'initialisation du microphone: ' + error.message);
                }
            }
        });

        /**
         * Affiche l'overlay d'enregistrement
         */
        function showRecordingOverlay() {
            recordingOverlay.classList.add('visible');
            recordingTimer.textContent = '0:00';
        }

        /**
         * Masque l'overlay d'enregistrement
         */
        function hideRecordingOverlay() {
            recordingOverlay.classList.remove('visible');
        }

        /**
         * Démarrer le timer d'enregistrement
         */
        function startRecordingTimer() {
            recordingTimer.textContent = '0:00';

            recordingTimerInterval = setInterval(() => {
                const elapsed = (Date.now() - recordingStartTime) / 1000;
                const formattedDuration = formatDuration(elapsed);
                recordingTimer.textContent = formattedDuration;

                // Limiter à 5 minutes
                if (elapsed >= 300) {
                    stopRecording();
                }
            }, 100);
        }

        /**
         * Arrêter le timer d'enregistrement
         */
        function stopRecordingTimer() {
            if (recordingTimerInterval) {
                clearInterval(recordingTimerInterval);
                recordingTimerInterval = null;
            }
        }

        /**
         * Arrêter l'enregistrement
         */
        function stopRecording() {
            if (!isRecording || !audioRecorder) {
                return;
            }

            console.log('🎙️ [AUDIO] Arrêt de l\'enregistrement');

            isRecording = false;
            audioRecorder.stop();
            stopRecordingTimer();
            recordAudioButton.classList.remove('recording');
        }

        /**
         * Annuler l'enregistrement
         */
        cancelRecordingBtn.addEventListener('click', () => {
            console.log('🎙️ [AUDIO] Annulation de l\'enregistrement');

            if (audioRecorder && audioRecorder.state !== 'inactive') {
                audioRecorder.stop();
            }

            // Arrêter le timer
            stopRecordingTimer();

            // Réinitialiser les variables
            isRecording = false;
            audioChunks = [];
            recordedAudioBlob = null;
            selectedAudioFile = null;
            selectedAudioDuration = null;
            recordingStartTime = null;

            // Masquer l'overlay
            hideRecordingOverlay();

            // Réinitialiser le bouton
            recordAudioButton.classList.remove('recording');
        });

        /**
         * Arrêter l'enregistrement (bouton Stop)
         */
        stopRecordingBtn.addEventListener('click', stopRecording);

        /**
         * Gère la suppression de l'audio sélectionné
         */
        removeAudioPreview.addEventListener('click', () => {
            selectedAudioFile = null;
            selectedAudioDuration = null;
            audioPreview.src = '';
            audioPreviewContainer.classList.remove('visible');
            hideValidationError();
        });

        // =====================================================
        // GESTION DE L'INDICATEUR DE FRAPPE
        // =====================================================

        /**
         * Affiche l'indicateur de frappe
         */
        function showTypingIndicator() {
            typingIndicator.classList.remove('hidden');
        }

        /**
         * Masque l'indicateur de frappe
         */
        function hideTypingIndicator() {
            typingIndicator.classList.add('hidden');
        }

        /**
         * Émet l'événement de frappe vers le serveur
         * @param {boolean} isTypingParam - Indique si l'utilisateur est en train de taper
         */
        function emitTypingEvent(isTypingParam) {
            if (!pusher || !currentConversation) {
                console.log('⚠️ [TYPING] Cannot emit: pusher or conversation not available');
                return;
            }

            const socketId = pusher?.connection?.socket_id || '';

            console.log('⌨️ [TYPING] Emitting typing event:', {
                conversation_id: currentConversation.id,
                user_id: currentUser.id,
                is_typing: isTypingParam
            });

            // Envoyer l'événement via l'API
            fetch(`${config.apiBaseUrl}/api/v1/conversations/${currentConversation.id}/typing`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'X-Application-ID': config.appId,
                    'X-Socket-ID': socketId,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    is_typing: isTypingParam
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('❌ [TYPING] Failed to emit typing event:', data.message);
                }
            })
            .catch(error => {
                console.error('❌ [TYPING] Error emitting typing event:', error);
            });
        }

        /**
         * Gestion de la frappe de l'utilisateur
         * Envoie l'événement de frappe avec debounce et timeout
         */
        function handleTyping() {
            // Si l'utilisateur n'était pas en train de taper, envoyer "typing: true"
            if (!isTyping) {
                isTyping = true;
                emitTypingEvent(true);
                console.log('⌨️ [TYPING] User started typing');
            }

            // Réinitialiser le timeout
            clearTimeout(typingTimeout);

            // Masquer l'indicateur et envoyer "typing: false" après 3 secondes d'inactivité
            typingTimeout = setTimeout(() => {
                if (isTyping) {
                    isTyping = false;
                    emitTypingEvent(false);
                    console.log('⌨️ [TYPING] User stopped typing (timeout)');
                }
            }, 3000);
        }

        // =====================================================
        // GESTION DE LA SAISIE (validation en temps réel)
        // =====================================================

        messageContent.addEventListener('input', () => {
            // Cacher le message d'erreur quand l'utilisateur corrige
            hideValidationError();

            // Gérer l'indicateur de frappe
            handleTyping();
        });

        // Send Message
        messageForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Vérifier qu'une conversation est sélectionnée
            if (!currentConversation) {
                return;
            }

            const content = messageContent.value.trim();
            const hasImage = selectedImageFile !== null;
            const hasAudio = selectedAudioFile !== null;

            // Vérifier qu'il y a au moins du contenu, une image ou un audio
            if (!content && !hasImage && !hasAudio) {
                showValidationError('Veuillez saisir un message, ajouter une image ou enregistrer un message vocal');
                return;
            }

            // Validation du contenu si du texte est présent
            if (content) {
                if (containsLinks(content)) {
                    showValidationError('Les liens sont interdits dans les messages');
                    return;
                }
                if (containsPhoneNumbers(content)) {
                    showValidationError('Les numéros de téléphone sont interdits dans les messages');
                    return;
                }
            }

            try {
                // Récupérer le socket_id pour le header X-Socket-ID (nécessaire pour toOthers())
                const socketId = pusher?.connection?.socket_id || '';
                console.log('📤 [SEND] Sending message with socket_id:', socketId);

                // Préparer les données à envoyer
                let headers = {
                    'Authorization': `Bearer ${token}`,
                    'X-Application-ID': config.appId,
                    'X-Socket-ID': socketId
                };

                let body = null;
                let contentType = 'application/json';

                // Si une image ou un audio est présent, utiliser FormData
                if (hasImage || hasAudio) {
                    isUploading = true;
                    uploadingOverlay.classList.add('visible');
                    imageUploadButton.disabled = true;

                    const formData = new FormData();
                    formData.append('content', content || '');
                    
                    if (hasImage) {
                        formData.append('file', selectedImageFile);
                        formData.append('type', 'image');
                    } else if (hasAudio) {
                        formData.append('file', selectedAudioFile);
                        formData.append('type', 'audio');
                        formData.append('duration', selectedAudioDuration);
                    } else {
                        formData.append('type', 'text');
                    }

                    body = formData;
                    delete headers['Content-Type']; // Laisser le navigateur définir le boundary
                    contentType = undefined;
                } else {
                    // Sinon, utiliser JSON
                    body = JSON.stringify({ content, type: 'text' });
                    headers['Content-Type'] = 'application/json';
                }

                const response = await fetch(
                    `${config.apiBaseUrl}/api/v1/conversations/${currentConversation.id}/messages`,
                    {
                        method: 'POST',
                        headers: headers,
                        body: body
                    }
                );

                const data = await response.json();

                if (data.success) {
                    console.log('✅ [SEND] Message envoyé:', data.data);

                    // IMPORTANT: Effacer le statut "Vu" des messages précédents
                    // Car le nouveau message n'a pas encore été lu par le destinataire
                    clearAllSeenStatus();

                    // Ajouter le message à l'UI
                    appendMessage(data.data, currentUser, true);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;

                    // Réinitialiser le formulaire
                    messageContent.value = '';
                    selectedImageFile = null;
                    selectedAudioFile = null;
                    selectedAudioDuration = null;
                    imageInput.value = '';
                    imagePreview.src = '';
                    imagePreviewContainer.classList.remove('visible');
                    audioPreview.src = '';
                    audioPreviewContainer.classList.remove('visible');
                    hideValidationError();

                    // ✅ METTRE À JOUR LA LISTE : Mettre à jour le dernier message dans la sidebar
                    // IMPORTANT: utiliser data.data.conversation_id pour mettre à jour la BONNE conversation
                    updateConversationInList(
                        data.data.conversation_id,
                        data.data,
                        currentUser
                    );
                } else {
                    // Afficher les erreurs de validation du backend
                    if (data.errors) {
                        let errorMessage = data.message || 'Erreur lors de l\'envoi du message';
                        
                        if (data.errors.file) {
                            const errors = data.errors.file;
                            errorMessage = Array.isArray(errors) ? errors.join(' ') : errors;
                        } else if (data.errors.duration) {
                            const errors = data.errors.duration;
                            errorMessage = Array.isArray(errors) ? errors.join(' ') : errors;
                        } else if (data.errors.content) {
                            const errors = data.errors.content;
                            errorMessage = Array.isArray(errors) ? errors.join(' ') : errors;
                        }
                        
                        showValidationError(errorMessage);
                    } else {
                        alert('Erreur: ' + (data.message || 'Erreur inconnue'));
                    }
                }
            } catch (error) {
                console.error('Failed to send message:', error);
                showValidationError('Erreur lors de l\'envoi du message');
            } finally {
                // Réinitialiser l'état d'upload
                isUploading = false;
                uploadingOverlay.classList.remove('visible');
                imageUploadButton.disabled = false;
            }
        });

        // Mark as Read
        async function markAsRead(conversationId) {
            // Éviter les appels concurrents
            if (isMarkingAsRead) {
                console.log('⚠️ [READ] Already marking as read, skipping');
                return;
            }

            isMarkingAsRead = true;

            try {
                console.log('📖 [READ] Marking conversation as read:', conversationId);

                // Animer la disparition du badge avant de le retirer
                const badge = document.getElementById(`badge-${conversationId}`);
                if (badge) {
                    badge.style.transform = 'scale(0)';
                    badge.style.opacity = '0';
                    console.log('📖 [READ] Badge animation started');
                }

                const response = await fetch(`${config.apiBaseUrl}/api/v1/conversations/${conversationId}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-Application-ID': config.appId
                    }
                });

                const data = await response.json();
                console.log('📖 [READ] API response:', data);

                if (data.success) {
                    // Mettre à jour le compteur après l'API
                    const conv = conversations.find(c => c.id === conversationId);
                    if (conv) {
                        console.log('📖 [READ] Before unread_count:', conv.unread_count);
                        conv.unread_count = 0;
                        console.log('📖 [READ] After unread_count:', conv.unread_count);
                        renderConversations();
                        console.log('✅ [READ] Badge reset to 0 and conversation list rendered');
                    }
                } else {
                    console.error('📖 [READ] API returned error:', data.message);
                }
            } catch (error) {
                console.error('📖 [READ] Failed to mark as read:', error);
            } finally {
                // Réinitialiser le flag après le traitement
                isMarkingAsRead = false;
            }
        }

        // Update Conversation in List
        function updateConversationInList(conversationId, newMessage, newSender) {
            // ✅ CORRECTION: Vérifier AVANT de marquer comme traité
            const wasAlreadyProcessed = newMessage && processedMessageIds.has(newMessage.id);
            
            // Vérifier si ce message a déjà été traité pour éviter les incréments multiples
            if (wasAlreadyProcessed) {
                console.log('⏭️ [UPDATE CONV] Message already processed, skipping:', newMessage.id);
                return;
            }

            // Marquer le message comme traité APRÈS la vérification
            if (newMessage) {
                processedMessageIds.set(newMessage.id, Date.now());
            }

            console.log('🔄 [UPDATE CONV] Updating conversation list', {
                conversationId,
                newMessage: newMessage?.content?.substring(0, 30) + '...',
                newSender: newSender?.name
            });

            const convIndex = conversations.findIndex(c => c.id === conversationId);

            if (convIndex === -1) {
                console.warn('⚠️ [UPDATE CONV] Conversation not found in list');
                return;
            }

            const conv = conversations[convIndex];

            // Mettre à jour le dernier message
            if (newMessage) {
                // Utiliser newSender si disponible (expéditeur réel), sinon utiliser newMessage.user
                const messageUser = newSender
                    ? { name: newSender.name, id: newSender.id }
                    : (newMessage.user || conv.last_message?.user);

                conv.last_message = {
                    ...conv.last_message,
                    content: newMessage.content,
                    created_at: newMessage.created_at,
                    user: messageUser
                };
            }

            // ✅ CORRECTION: Incrémenter le compteur de messages non lus SEULEMENT si :
            // 1. Le message vient d'un autre utilisateur (pas de l'utilisateur courant)
            // 2. La conversation n'est PAS ouverte/active
            const isFromOtherUser = newSender && newSender.id !== currentUser?.id;
            const isConversationNotOpen = !currentConversation || currentConversation.id !== conversationId;

            const shouldIncrement = isFromOtherUser && isConversationNotOpen;
            if (shouldIncrement) {
                conv.unread_count = (conv.unread_count || 0) + 1;
                console.log('📈 [BADGE] Badge incremented:', {
                    conversationId,
                    newCount: conv.unread_count,
                    from: newSender?.name,
                    to: currentUser?.name
                });
            }

            // Déplacer la conversation en haut de la liste
            conversations.splice(convIndex, 1);
            conversations.unshift(conv);

            // Re-render la liste
            renderConversations();

            // Si le compteur a été incrémenté, redéclencher l'animation pulse sur le badge
            // ✅ CORRECTION : Utiliser requestAnimationFrame pour s'assurer que le DOM est prêt
            if (shouldIncrement) {
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        const badge = document.getElementById(`badge-${conversationId}`);
                        if (badge) {
                            console.log('🎨 [BADGE ANIMATION] Triggering pulse animation for badge:', `badge-${conversationId}`);
                            // Retirer et rajouter la classe pour redéclencher l'animation
                            badge.classList.remove('badge-pulse');
                            void badge.offsetWidth; // Force reflow
                            badge.classList.add('badge-pulse');
                            badge.classList.add('badge-appear'); // Animation d'apparition
                            console.log('✅ [BADGE ANIMATION] Animation classes added');
                        } else {
                            console.warn('⚠️ [BADGE ANIMATION] Badge not found:', `badge-${conversationId}`);
                        }
                    });
                });
            }

            console.log('✅ [UPDATE CONV] Conversation list updated');
        }

        // Create Conversation
        createConversationBtn.addEventListener('click', async () => {
            usersList.classList.toggle('hidden');
            if (!usersList.classList.contains('hidden')) {
                loadUsers();
            }
        });

        cancelNewConversation.addEventListener('click', () => {
            usersList.classList.add('hidden');
        });

        async function loadUsers() {
            usersListContent.innerHTML = '<p class="text-gray-500 text-center">Chargement...</p>';

            try {
                const response = await fetch(`${config.apiBaseUrl}/api/v1/users`, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-Application-ID': config.appId
                    }
                });

                const data = await response.json();

                if (data.success && data.data) {
                    // Filter out current user from list
                    const users = data.data.filter(u => u.id !== currentUser.id);

                    if (users.length === 0) {
                        usersListContent.innerHTML = '<p class="text-gray-500 text-center">Aucun autre utilisateur disponible</p>';
                        return;
                    }

                    usersListContent.innerHTML = users.map(user => `
                        <div onclick="createConversation(${user.id})"
                            class="p-2 hover:bg-gray-100 rounded cursor-pointer transition-colors">
                            <p class="font-medium">${user.name}</p>
                            <p class="text-sm text-gray-500">${user.email}</p>
                        </div>
                    `).join('');
                } else {
                    usersListContent.innerHTML = `<p class="text-red-500 text-center py-4">${data.message || 'Erreur lors du chargement des utilisateurs'}</p>`;
                }
            } catch (error) {
                console.error('Failed to load users:', error);
                usersListContent.innerHTML = '<p class="text-red-500 text-center py-4">Erreur lors du chargement des utilisateurs</p>';
            }
        }

        async function createConversation(userId) {
            try {
                const response = await fetch(`${config.apiBaseUrl}/api/v1/conversations`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-Application-ID': config.appId
                    },
                    body: JSON.stringify({
                        app_id: config.appId,
                        type: 'direct',
                        participant_ids: [userId]
                    })
                });

                const data = await response.json();

                if (data.success) {
                    usersList.classList.add('hidden');
                    loadConversations();
                    selectConversation(data.data.id);
                } else {
                    alert('Erreur: ' + (data.message || 'Erreur inconnue'));
                    console.error('Create conversation error:', data);
                }
            } catch (error) {
                console.error('Failed to create conversation:', error);
                alert('Erreur lors de la création de la conversation');
            }
        }

        // Configuration Modal
        document.getElementById('saveConfig').addEventListener('click', () => {
            config.reverbKey = document.getElementById('reverbKey').value;
            config.reverbHost = document.getElementById('reverbHost').value;
            config.reverbPort = document.getElementById('reverbPort').value;
            config.appId = document.getElementById('appId').value;
            config.apiBaseUrl = document.getElementById('apiBaseUrl').value;

            // Save to localStorage
            localStorage.setItem('reverbKey', config.reverbKey);
            localStorage.setItem('reverbHost', config.reverbHost);
            localStorage.setItem('reverbPort', config.reverbPort);
            localStorage.setItem('appId', config.appId);
            localStorage.setItem('apiBaseUrl', config.apiBaseUrl);

            configModal.classList.add('hidden');

            // Reconnect with new config
            if (pusher) {
                pusher.disconnect();
            }
            connectWebSocket();
            loadConversations();
        });

        document.getElementById('closeConfig').addEventListener('click', () => {
            configModal.classList.add('hidden');
        });

        function loadConfig() {
            document.getElementById('reverbKey').value = config.reverbKey;
            document.getElementById('reverbHost').value = config.reverbHost;
            document.getElementById('reverbPort').value = config.reverbPort;
            document.getElementById('appId').value = config.appId;
            document.getElementById('apiBaseUrl').value = config.apiBaseUrl;
        }

        // Show config modal on double-click of connection status
        // =====================================================
        // ÉDITION DE MESSAGES
        // =====================================================

        /**
         * Démarre le mode édition pour un message
         * @param {number} messageId - ID du message à éditer
         */
        function startEditing(messageId) {
            // Récupérer le conteneur du message via le parent de message-bubble
            const messageBubble = document.querySelector(`[data-message-id="${messageId}"].message-bubble`);
            const messageBodyEl = document.getElementById(`message-body-${messageId}`);
            const messageDiv = messageBubble ? messageBubble.parentElement : null;

            if (!messageBodyEl || !messageBubble || !messageDiv) {
                console.error('❌ [EDIT] Message elements not found:', messageId, {
                    messageBodyEl: !!messageBodyEl,
                    messageBubble: !!messageBubble,
                    messageDiv: !!messageDiv
                });
                return;
            }

            // Récupérer le type de message
            const messageType = messageDiv.dataset.messageType || 'text';

            // Vérifier que le message n'est PAS un message vocal (les messages audio ne peuvent pas être modifiés)
            if (messageType === 'audio') {
                console.warn('⚠️ [EDIT] Cannot edit audio message:', messageId);
                return;
            }

            console.log('✅ [EDIT] Starting edit for message:', messageId);
            console.log('✅ [EDIT] Message type:', messageDiv.dataset.messageType);
            console.log('✅ [EDIT] File URL:', messageDiv.dataset.messageFileUrl);
            const originalFileUrl = messageDiv.dataset.messageFileUrl || '';

            // Récupérer le contenu original
            const originalContent = messageBodyEl.dataset.originalContent || '';

            // Créer le formulaire d'édition
            const editingContainer = document.createElement('div');
            editingContainer.className = 'editing-container';
            editingContainer.id = `editing-container-${messageId}`;

            // Interface différente selon le type de message
            if (messageType === 'image') {
                console.log('✅ [EDIT] Creating file input for image message');
                // Pour les images : afficher input file
                editingContainer.innerHTML = `
                    <div class="mb-2">
                        <p class="text-sm text-gray-600 mb-2">Sélectionner une nouvelle image :</p>
                        <input
                            type="file"
                            id="edit-file-${messageId}"
                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                            class="w-full p-2 border-2 border-blue-500 rounded-lg"
                        />
                    </div>
                    ${originalFileUrl ? `
                        <div class="mb-2">
                            <p class="text-xs text-gray-500 mb-1">Image actuelle :</p>
                            <img src="${originalFileUrl}" alt="Image actuelle" class="max-w-[150px] rounded-lg">
                        </div>
                    ` : ''}
                    <div class="edit-buttons">
                        <button type="button" id="cancel-edit-${messageId}" class="edit-cancel-btn">
                            Annuler
                        </button>
                        <button type="button" id="save-edit-${messageId}" class="edit-save-btn">
                            Valider
                        </button>
                    </div>
                `;
            } else {
                console.log('✅ [EDIT] Creating textarea for text message');
                // Pour les textes : afficher textarea
                editingContainer.innerHTML = `
                    <textarea
                        id="edit-textarea-${messageId}"
                        class="edit-textarea"
                        rows="3"
                    >${originalContent}</textarea>
                    <div class="edit-buttons">
                        <button type="button" id="cancel-edit-${messageId}" class="edit-cancel-btn">
                            Annuler
                        </button>
                        <button type="button" id="save-edit-${messageId}" class="edit-save-btn">
                            Valider
                        </button>
                    </div>
                `;
            }

            // Remplacer le contenu par le formulaire d'édition
            messageBodyEl.innerHTML = '';
            messageBodyEl.appendChild(editingContainer);

            // Focus sur l'élément d'édition
            if (messageType === 'image') {
                const fileInput = editingContainer.querySelector('input[type="file"]');
                fileInput.focus();
            } else {
                const textarea = editingContainer.querySelector('textarea');
                textarea.focus();
                textarea.select();
            }

            // Attacher les événements
            const saveBtn = editingContainer.querySelector('.edit-save-btn');
            const cancelBtn = editingContainer.querySelector('.edit-cancel-btn');

            saveBtn.addEventListener('click', () => saveEdit(messageId));
            cancelBtn.addEventListener('click', () => cancelEdit(messageId));

            // Raccourcis clavier : Échap pour annuler
            const editElement = messageType === 'image'
                ? editingContainer.querySelector('input[type="file"]')
                : editingContainer.querySelector('textarea');

            editElement.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelEdit(messageId);
                }
            });

            // Pour les textes, ajouter le raccourci Entrée
            if (messageType !== 'image') {
                const textarea = editingContainer.querySelector('textarea');
                textarea.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        saveEdit(messageId);
                    }
                });
            }
        }

        /**
         * Sauvegarde les modifications d'un message
         * @param {number} messageId - ID du message à sauvegarder
         */
        async function saveEdit(messageId) {
            // Récupérer le conteneur du message via le parent de message-bubble
            const messageBubble = document.querySelector(`[data-message-id="${messageId}"].message-bubble`);
            const messageDiv = messageBubble ? messageBubble.parentElement : null;
            const messageBodyEl = document.getElementById(`message-body-${messageId}`);
            const saveBtn = document.getElementById(`save-edit-${messageId}`);

            if (!messageDiv || !saveBtn || !messageBubble) {
                console.error('❌ [EDIT] Edit elements not found:', messageId, {
                    messageDiv: !!messageDiv,
                    saveBtn: !!saveBtn,
                    messageBubble: !!messageBubble
                });
                return;
            }

            // Vérifier que la conversation est ouverte
            if (!currentConversation || !currentConversation.id) {
                console.error('❌ [EDIT] No active conversation', currentConversation);
                alert('Aucune conversation active. Veuillez sélectionner une conversation.');
                return;
            }

            // Récupérer le type de message
            const messageType = messageDiv.dataset.messageType || 'text';

            // Vérifier que le message n'est PAS un message vocal (sécurité supplémentaire)
            if (messageType === 'audio') {
                console.warn('⚠️ [EDIT] Cannot save audio message edit:', messageId);
                alert('Les messages vocaux ne peuvent pas être modifiés');
                return;
            }

            console.log('✅ [EDIT] Saving edit for message:', messageId, {
                type: messageType,
                fileUrl: messageDiv.dataset.messageFileUrl
            });

            let body = null;
            let headers = {
                'Authorization': `Bearer ${token}`,
                'X-Application-ID': config.appId
            };

            if (messageType === 'image') {
                // Pour les images : utiliser FormData avec le fichier
                const fileInput = document.getElementById(`edit-file-${messageId}`);
                const file = fileInput ? fileInput.files[0] : null;

                console.log('✅ [EDIT] File input element:', fileInput);
                console.log('✅ [EDIT] Selected file:', file ? {
                    name: file.name,
                    size: file.size,
                    type: file.type
                } : 'No file selected');

                if (!file) {
                    alert('Veuillez sélectionner une nouvelle image');
                    return;
                }

                // Valider le type de fichier
                if (!file.type.match(/image\/(jpeg|jpg|png|gif|webp)/)) {
                    alert('Le fichier doit être une image (JPEG, PNG, GIF ou WebP)');
                    return;
                }

                // Valider la taille du fichier (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Le fichier ne doit pas dépasser 5Mo');
                    return;
                }

                // Créer FormData
                const formData = new FormData();
                formData.append('_method', 'PUT'); // Laravel a besoin de cela pour les requêtes PUT avec FormData
                formData.append('file', file);

                // Vérifier que le fichier est bien dans le FormData
                console.log('✅ [EDIT] FormData created');
                console.log('✅ [EDIT] FormData has file entry:', formData.has('file'));
                for (let pair of formData.entries()) {
                    console.log('✅ [EDIT] FormData entry:', pair[0], pair[1].name, pair[1].size, pair[1].type);
                }

                body = formData;

                // IMPORTANT: Pour FormData, ne PAS définir Content-Type
                // Le navigateur le définit automatiquement avec le boundary correct
                // MAIS on garde les headers d'authentification !
                delete headers['Content-Type'];
            } else {
                // Pour les textes : utiliser JSON avec le contenu
                const textarea = document.getElementById(`edit-textarea-${messageId}`);
                const newContent = textarea ? textarea.value.trim() : '';

                // Validation : ne pas sauvegarder un message vide
                if (!newContent) {
                    alert('Le message ne peut pas être vide');
                    return;
                }

                // Validation backend : vérifier les liens et numéros de téléphone
                if (containsLinks(newContent)) {
                    alert('Les liens ne sont pas autorisés dans les messages');
                    return;
                }

                if (containsPhoneNumbers(newContent)) {
                    alert('Les numéros de téléphone ne sont pas autorisés dans les messages');
                    return;
                }

                body = JSON.stringify({ content: newContent });
                headers['Content-Type'] = 'application/json';
            }

            // Afficher le loader
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="edit-loader"></span>Envoi...';

            try {
                // Pour FormData, envoyer en POST avec _method=PUT (Laravel a besoin de cela pour multipart)
                const httpMethod = (messageType === 'image') ? 'POST' : 'PUT';

                console.log('📝 [EDIT] Sending update request:', {
                    messageId,
                    conversationId: currentConversation.id,
                    messageType: messageType,
                    httpMethod: httpMethod,
                    hasFormData: body instanceof FormData,
                    headers: headers
                });

                const response = await fetch(
                    `${config.apiBaseUrl}/api/v1/conversations/${currentConversation.id}/messages/${messageId}`,
                    {
                        method: httpMethod,
                        headers: headers,
                        body: body
                    }
                );

                console.log('📝 [EDIT] Response status:', response.status, response.statusText);

                // Lire la réponse avant de parser
                const responseText = await response.text();
                console.log('📝 [EDIT] Response text:', responseText);

                const data = JSON.parse(responseText);
                console.log('📝 [EDIT] Parsed response data:', data);

                if (!response.ok) {
                    console.error('📝 [EDIT] API returned error status:', response.status);
                    throw new Error(data.message || `HTTP ${response.status}: ${response.statusText}`);
                }

                if (data.success) {
                    const updatedMessage = data.data;

                    console.log('✅ [EDIT] Message updated successfully:', updatedMessage);

                    // Mettre à jour le contenu
                    let messageBody = '';
                    if (updatedMessage.type === 'image' && updatedMessage.file_url) {
                        messageBody += `<img src="${updatedMessage.file_url}" alt="${updatedMessage.file_name || 'Image'}" class="message-image" onclick="window.open('${updatedMessage.file_url}', '_blank')">`;
                    }
                    if (updatedMessage.content) {
                        messageBody += `<p class="text-gray-800 ${messageBody ? 'mt-2' : ''}" data-content="true">${updatedMessage.content}</p>`;
                    }

                    messageBodyEl.innerHTML = messageBody;
                    messageBodyEl.dataset.originalContent = updatedMessage.content || '';

                    // Mettre à jour les attributs du conteneur
                    messageDiv.dataset.messageType = updatedMessage.type;
                    messageDiv.dataset.messageFileUrl = updatedMessage.file_url || '';

                    // Mettre à jour l'indicateur "édité"
                    let editedIndicator = messageBubble.querySelector('.edited-indicator');
                    if (!editedIndicator) {
                        editedIndicator = document.createElement('span');
                        editedIndicator.className = 'edited-indicator';
                        editedIndicator.innerHTML = '<span class="edited-icon">✎</span>Modifié';
                        messageBubble.appendChild(editedIndicator);
                    } else {
                        editedIndicator.style.display = 'inline-flex';
                    }

                    console.log('✅ [EDIT] UI updated successfully');
                } else {
                    alert('Erreur lors de la modification : ' + (data.message || 'Erreur inconnue'));
                    // Réactiver le bouton en cas d'erreur
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = 'Valider';
                    }
                }
            } catch (error) {
                console.error('📝 [EDIT] Failed to edit message:', error);
                console.error('📝 [EDIT] Error stack:', error.stack);
                console.error('📝 [EDIT] Error details:', {
                    name: error.name,
                    message: error.message,
                    currentConversation: currentConversation?.id,
                    messageId: messageId,
                    messageType: messageType,
                    token: token ? 'exists' : 'missing'
                });

                // Réactiver le bouton en cas d'erreur
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = 'Valider';
                }

                alert('Erreur de connexion. Veuillez réessayer.\n\nDétails: ' + error.message);
            }
        }

        /**
         * Annule l'édition d'un message
         * @param {number} messageId - ID du message dont l'édition est annulée
         */
        function cancelEdit(messageId) {
            // Récupérer le conteneur du message via le parent de message-bubble
            const messageBubble = document.querySelector(`[data-message-id="${messageId}"].message-bubble`);
            const messageBodyEl = document.getElementById(`message-body-${messageId}`);
            const messageDiv = messageBubble ? messageBubble.parentElement : null;

            if (!messageBodyEl || !messageBubble || !messageDiv) {
                console.error('❌ [EDIT CANCEL] Message elements not found:', messageId, {
                    messageBodyEl: !!messageBodyEl,
                    messageBubble: !!messageBubble,
                    messageDiv: !!messageDiv
                });
                return;
            }

            // Récupérer le type de message et les données
            const messageType = messageDiv.dataset.messageType || 'text';
            const originalFileUrl = messageDiv.dataset.messageFileUrl || '';
            const originalContent = messageBodyEl.dataset.originalContent || '';

            console.log('✅ [EDIT CANCEL] Canceling edit for message:', messageId, {
                type: messageType,
                fileUrl: originalFileUrl,
                content: originalContent
            });

            // Reconstruire le contenu du message selon le type
            let messageBody = '';

            if (messageType === 'image' && originalFileUrl) {
                // Pour les images : afficher l'image
                messageBody += `<img src="${originalFileUrl}" alt="Image" class="message-image" onclick="window.open('${originalFileUrl}', '_blank')">`;
            }

            // Ajouter le texte s'il existe
            if (originalContent) {
                messageBody += `<p class="text-gray-800 ${messageBody ? 'mt-2' : ''}" data-content="true">${originalContent}</p>`;
            }

            // Restaurer le contenu original
            messageBodyEl.innerHTML = messageBody;
        }

        // =====================================================
        // SUPPRESSION DE MESSAGES
        // =====================================================

        /**
         * Affiche la modal de confirmation de suppression
         */
        function confirmDeleteMessage(messageId) {
            console.log('🗑️ [DELETE] Confirmation requested for message:', messageId);
            messageToDelete = messageId;
            deleteModalOverlay.classList.add('visible');
        }

        /**
         * Cache la modal de confirmation de suppression
         */
        function hideDeleteModal() {
            deleteModalOverlay.classList.remove('visible');
            messageToDelete = null;
        }

        /**
         * Exécute la suppression du message
         */
        async function executeDeleteMessage() {
            if (!messageToDelete || isDeleting) {
                return;
            }

            const messageId = messageToDelete;
            console.log('🗑️ [DELETE] Executing delete for message:', messageId);

            isDeleting = true;
            confirmDeleteBtn.disabled = true;
            confirmDeleteBtn.textContent = 'Suppression...';

            try {
                const response = await fetch(
                    `${config.apiBaseUrl}/api/v1/conversations/${currentConversation.id}/messages/${messageId}`,
                    {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                            'X-Application-ID': config.appId,
                            'X-Socket-ID': pusher?.connection?.socket_id || ''
                        }
                    }
                );

                const data = await response.json();

                if (data.success) {
                    console.log('✅ [DELETE] Message deleted successfully');

                    // Mettre à jour l'UI localement
                    markMessageAsDeleted(messageId);

                    // Fermer la modal
                    hideDeleteModal();
                } else {
                    console.error('❌ [DELETE] Delete failed:', data.message);
                    alert('Erreur: ' + data.message);
                }
            } catch (error) {
                console.error('❌ [DELETE] Delete error:', error);
                alert('Erreur lors de la suppression du message');
            } finally {
                isDeleting = false;
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.textContent = 'Supprimer';
            }
        }

        /**
         * Met à jour l'UI pour marquer un message comme supprimé
         */
        function markMessageAsDeleted(messageId) {
            console.log('🗑️ [DELETE] Marking message as deleted in UI:', messageId);

            // Trouver le conteneur du message
            const messageContainer = document.querySelector(`[data-message-id="${messageId}"]`);
            const messageBubble = document.querySelector(`[data-message-id="${messageId}"].message-bubble`);

            if (!messageBubble) {
                console.error('❌ [DELETE] Message bubble not found:', messageId);
                return;
            }

            // Mettre à jour le dataset
            const parentDiv = messageBubble.parentElement;
            if (parentDiv) {
                parentDiv.dataset.isDeleted = 'true';
            }

            // Ajouter la classe pour le style supprimé
            messageBubble.classList.add('message-deleted');

            // Remplacer le contenu par l'indicateur de suppression
            const messageBodyEl = document.getElementById(`message-body-${messageId}`);
            if (messageBodyEl) {
                messageBodyEl.innerHTML = `
                    <div class="message-deleted-content">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        <span>Message supprimé</span>
                    </div>
                `;
                messageBodyEl.dataset.originalContent = '';
            }

            // Supprimer les boutons d'édition et de suppression
            const editButton = messageBubble.querySelector('.edit-button');
            const deleteButton = messageBubble.querySelector('.delete-button');
            if (editButton) editButton.remove();
            if (deleteButton) deleteButton.remove();

            // Supprimer l'indicateur "modifié" si présent
            const editedIndicator = messageBubble.querySelector('.edited-indicator');
            if (editedIndicator) editedIndicator.remove();

            // Désactiver le double-clic pour l'édition
            messageBubble.removeAttribute('ondblclick');

            console.log('✅ [DELETE] Message UI updated successfully');
        }

        // Event listeners pour la modal de suppression
        cancelDeleteBtn.addEventListener('click', hideDeleteModal);
        confirmDeleteBtn.addEventListener('click', executeDeleteMessage);

        // Fermer la modal en cliquant en dehors
        deleteModalOverlay.addEventListener('click', (e) => {
            if (e.target === deleteModalOverlay) {
                hideDeleteModal();
            }
        });

        // Fermer la modal avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && deleteModalOverlay.classList.contains('visible')) {
                hideDeleteModal();
            }
        });

        // =====================================================
        // ÉVÉNEMENTS GLOBAUX
        // =====================================================

        connectionStatus.addEventListener('dblclick', () => {
            configModal.classList.remove('hidden');
        });

        // Initialize
        init();
    </script>
</body>
</html>
