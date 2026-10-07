@extends('layouts.payphone-tool', [
    'title' => 'Acceso a cobros | Booty Fitness',
    'accessView' => true,
])

@section('content')
    <style>
        .payphone-access-screen,
        .payphone-access-screen * {
            box-sizing: border-box;
        }

        .payphone-access-screen {
            position: fixed;
            inset: 0;
            z-index: 10;
            width: 100%;
            max-width: 100%;
            min-height: 100vh;
            min-height: 100svh;
            overflow-x: hidden;
            overflow-y: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: #F7F4FA;
            color: #292033;
        }

        .payphone-access-card {
            width: min(100%, 390px);
            max-width: 390px;
            margin: 0 auto;
            padding: 24px;
            border: 1px solid #E8DDED;
            border-radius: 24px;
            background: #FFFFFF;
            box-shadow: 0 16px 38px rgba(64, 38, 77, 0.10);
        }

        .payphone-access-header {
            text-align: center;
        }

        .payphone-access-lock {
            width: 40px;
            height: 40px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #E4D4EA;
            border-radius: 50%;
            background: #F3EAF7;
            color: #713B86;
        }

        .payphone-access-lock svg {
            display: block;
        }

        .payphone-access-brand {
            margin: 14px 0 0;
            color: #713B86;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.28em;
            line-height: 1.2;
        }

        .payphone-access-title {
            margin: 18px 0 0;
            color: #292033;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.25;
        }

        .payphone-access-subtitle {
            margin: 8px 0 0;
            color: #6B6273;
            font-size: 14px;
            line-height: 1.45;
        }

        .payphone-access-alert {
            margin-top: 20px;
            padding: 12px 14px;
            border-radius: 14px;
            font-size: 14px;
            line-height: 1.45;
        }

        .payphone-access-alert--error {
            border: 1px solid #E8C7D0;
            background: #FFF4F6;
            color: #8B3049;
        }

        .payphone-access-alert--success {
            border: 1px solid #C9E7D5;
            background: #F1FBF4;
            color: #246A40;
        }

        .payphone-access-alert--warning {
            border: 1px solid #EADBAF;
            background: #FFFBED;
            color: #755B18;
        }

        .payphone-access-form {
            margin-top: 24px;
        }

        .payphone-access-sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .payphone-access-code-shell {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #E8DDED;
            border-radius: 14px;
            background: #FCFAFD;
        }

        .payphone-access-code-shell:focus-within {
            border-color: #A981B5;
            box-shadow: 0 0 0 3px rgba(113, 59, 134, 0.14);
        }

        .payphone-access-code {
            display: block;
            width: 100% !important;
            min-width: 0;
            height: 42px;
            padding: 0 !important;
            border: 0 !important;
            outline: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            color: #292033 !important;
            font-size: 26px !important;
            font-weight: 700;
            letter-spacing: 0.42em;
            line-height: 42px;
            text-align: center;
        }

        .payphone-access-code:focus {
            border: 0 !important;
            outline: 0 !important;
            box-shadow: none !important;
        }

        .payphone-access-help {
            margin: 8px 0 0;
            color: #6B6273;
            font-size: 12px;
            line-height: 1.4;
            text-align: center;
        }

        .payphone-access-keyboard {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            width: 100%;
            max-width: 252px;
            margin: 22px auto 0;
        }

        .payphone-access-key {
            width: 64px;
            height: 64px;
            max-width: 100%;
            justify-self: center;
            padding: 0;
            border: 1px solid #E4D4EA;
            border-radius: 50%;
            background: #F3EAF7;
            color: #292033;
            cursor: pointer;
            font-size: 24px;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            transition: background-color 150ms ease, border-color 150ms ease, transform 100ms ease;
        }

        .payphone-access-key:hover {
            border-color: #C9A9D3;
            background: #EDE0F2;
        }

        .payphone-access-key:focus-visible,
        .payphone-access-submit:focus-visible {
            outline: 3px solid rgba(113, 59, 134, 0.35);
            outline-offset: 3px;
        }

        .payphone-access-key:active,
        .payphone-access-submit:active {
            transform: scale(0.96);
        }

        .payphone-access-key--clear {
            padding: 0 4px;
            color: #713B86;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .payphone-access-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 48px;
            margin-top: 24px;
            padding: 12px 20px;
            border: 1px solid #713B86;
            border-radius: 14px;
            background: #713B86;
            color: #FFFFFF;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.16em;
            line-height: 1.2;
            text-align: center;
            transition: background-color 150ms ease, transform 100ms ease;
        }

        .payphone-access-submit:hover {
            background: #623174;
        }

        @media (max-height: 720px) {
            .payphone-access-screen {
                align-items: flex-start;
            }
        }

        @media (max-width: 360px) {
            .payphone-access-screen {
                padding: 20px 16px;
            }

            .payphone-access-card {
                padding: 20px;
            }

            .payphone-access-keyboard {
                gap: 12px;
                margin-top: 18px;
            }

            .payphone-access-submit {
                margin-top: 20px;
            }
        }
    </style>

    <div class="payphone-access-screen" data-payphone-access>
        <div class="payphone-access-card">
            <header class="payphone-access-header">
                <div class="payphone-access-lock" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.7" />
                        <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                        <circle cx="12" cy="15" r="1" fill="currentColor" />
                    </svg>
                </div>
                <p class="payphone-access-brand">BOOTYFITNESS</p>
                <h1 id="access-title" class="payphone-access-title">Acceso a cobros</h1>
                <p class="payphone-access-subtitle">Ingresa tu código de acceso</p>
            </header>

            @if($configurationError)
                <div class="payphone-access-alert payphone-access-alert--warning" role="alert">
                    La herramienta no esta disponible en este momento.
                </div>
            @else
                @if(session('success'))
                    <div class="payphone-access-alert payphone-access-alert--success" role="status">
                        {{ session('success') }}
                    </div>
                @endif

                @if($rateLimited)
                    <div class="payphone-access-alert payphone-access-alert--error" role="alert">
                        Demasiados intentos. Espera un momento antes de volver a intentar.
                    </div>
                @endif

                @if($errors->any())
                    <div class="payphone-access-alert payphone-access-alert--error" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('payphone-tool.access') }}" method="POST" class="payphone-access-form" data-access-form>
                    @csrf

                    <label for="access-code" class="payphone-access-sr-only">Codigo de acceso</label>
                    <div class="payphone-access-code-shell">
                        <input
                            id="access-code"
                            name="code"
                            type="password"
                            inputmode="none"
                            autocomplete="off"
                            required
                            readonly
                            aria-describedby="access-code-help"
                            data-access-code
                            class="payphone-access-code"
                        >
                    </div>
                    <p id="access-code-help" class="payphone-access-help">Los caracteres se mantienen ocultos.</p>

                    <div class="payphone-access-keyboard" aria-label="Teclado numérico">
                        @foreach([1, 2, 3, 4, 5, 6, 7, 8, 9] as $digit)
                            <button type="button" data-key="{{ $digit }}" aria-label="Número {{ $digit }}" class="payphone-access-key">
                                {{ $digit }}
                            </button>
                        @endforeach

                        <button type="button" data-clear-key aria-label="Borrar último carácter" class="payphone-access-key payphone-access-key--clear">
                            Borrar
                        </button>
                        <button type="button" data-key="0" aria-label="Número 0" class="payphone-access-key">
                            0
                        </button>
                        <span aria-hidden="true"></span>
                    </div>

                    <button type="submit" class="payphone-access-submit">
                        INGRESAR
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const input = document.querySelector('[data-access-code]');
            const form = document.querySelector('[data-access-form]');
            if (!input || !form) return;

            document.querySelectorAll('[data-key]').forEach((button) => {
                button.addEventListener('click', () => {
                    input.value += button.dataset.key || '';
                });
            });

            document.querySelector('[data-clear-key]')?.addEventListener('click', () => {
                input.value = input.value.slice(0, -1);
            });

            form.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace') {
                    event.preventDefault();
                    input.value = input.value.slice(0, -1);
                    return;
                }

                if (event.key === 'Enter') {
                    event.preventDefault();
                    form.requestSubmit();
                    return;
                }

                if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                    event.preventDefault();
                    input.value += event.key;
                }
            });
        })();
    </script>
@endpush
