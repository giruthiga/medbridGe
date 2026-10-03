<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'medbridGe') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* Background image — dark medical data feel */
            body {
                background-color: #0a0a0a;
                background-image:
                    radial-gradient(ellipse 80% 50% at 50% -20%, rgba(16, 185, 129, 0.15), transparent),
                    radial-gradient(ellipse 60% 40% at 80% 100%, rgba(16, 185, 129, 0.08), transparent),
                    linear-gradient(rgba(10, 10, 10, 0.95), rgba(10, 10, 10, 0.98)),
                    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cdefs%3E%3Cpattern id='grid' width='40' height='40' patternUnits='userSpaceOnUse'%3E%3Cpath d='M 40 0 L 0 0 0 40' fill='none' stroke='%231a1a1a' stroke-width='0.5'/%3E%3C/pattern%3E%3C/defs%3E%3Crect width='100' height='100' fill='url(%23grid)'/%3E%3C/svg%3E");
                background-attachment: fixed;
                min-height: 100vh;
                margin: 0;
            }

            /* Inputs — force dark theme */
            input:not([type="checkbox"]) {
                background-color: #0a0a0a !important;
                color: #f5f5f5 !important;
                border: 1px solid #2a2a2a !important;
                border-radius: 12px !important;
                padding: 12px 16px !important;
                font-size: 14px !important;
                width: 100% !important;
                transition: all 0.2s ease !important;
                box-sizing: border-box !important;
            }

            input:not([type="checkbox"]):focus {
                outline: none !important;
                border-color: #10b981 !important;
                box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
            }

            input::placeholder {
                color: #5a5a5a !important;
                opacity: 1 !important;
            }

            /* Checkbox — clean alignment */
            input[type="checkbox"] {
                appearance: none !important;
                -webkit-appearance: none !important;
                width: 18px !important;
                height: 18px !important;
                border: 1.5px solid #2a2a2a !important;
                border-radius: 5px !important;
                background-color: #0a0a0a !important;
                cursor: pointer !important;
                position: relative !important;
                flex-shrink: 0 !important;
                transition: all 0.15s ease !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            input[type="checkbox"]:hover {
                border-color: #10b981 !important;
            }

            input[type="checkbox"]:checked {
                background-color: #10b981 !important;
                border-color: #10b981 !important;
            }

            input[type="checkbox"]:checked::after {
                content: '' !important;
                position: absolute !important;
                left: 5px !important;
                top: 1px !important;
                width: 5px !important;
                height: 10px !important;
                border: solid #000 !important;
                border-width: 0 2.5px 2.5px 0 !important;
                transform: rotate(45deg) !important;
            }

            /* Labels */
            label {
                color: #8a8a8a !important;
                font-size: 11px !important;
                font-weight: 600 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.1em !important;
                display: block !important;
                margin-bottom: 8px !important;
            }

            /* Button */
            button[type="submit"] {
                background-color: #10b981 !important;
                color: #000 !important;
                font-weight: 700 !important;
                padding: 14px 24px !important;
                border-radius: 12px !important;
                width: 100% !important;
                font-size: 14px !important;
                border: none !important;
                cursor: pointer !important;
                transition: all 0.2s ease !important;
            }

            button[type="submit"]:hover {
                background-color: #34d399 !important;
                box-shadow: 0 0 30px -8px rgba(16, 185, 129, 0.6) !important;
            }
        </style>
    </head>
    <body class="font-sans text-med-text antialiased">

        <div style="position: relative; min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 24px;">

            {{-- Brand --}}
            <a href="/" style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; text-decoration: none;">
                <div style="width: 44px; height: 44px; border-radius: 14px; background-color: #10b981; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 24px -4px rgba(16, 185, 129, 0.6);">
                    <span style="color: #000; font-weight: 800; font-size: 22px;">m</span>
                </div>
                <span style="font-size: 30px; font-weight: 800; color: #f5f5f5; letter-spacing: -0.02em;">
                    medbrid<span style="color: #10b981;">G</span>e
                </span>
            </a>

            <p style="font-size: 13px; color: #8a8a8a; margin-bottom: 40px; text-align: center; max-width: 320px;">
                Scan. Score. Sell. — Data readiness for HIS adoption.
            </p>

            {{-- Card --}}
            <div style="width: 100%; max-width: 440px; background-color: rgba(19, 19, 19, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid #2a2a2a; border-radius: 24px; padding: 32px; box-shadow: 0 20px 60px -10px rgba(0, 0, 0, 0.7);">
                {{ $slot }}
            </div>

            <p style="margin-top: 32px; font-size: 12px; color: #5a5a5a;">
                © {{ date('Y') }} medbridGe. All rights reserved.
            </p>
        </div>
    </body>
</html>