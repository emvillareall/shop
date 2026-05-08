<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .login-bg {
            min-height: 100vh;
            background: #ccb4d5;
            display: grid;
            place-items: center;
            padding: 32px 16px;
        }
        .login-wrap {
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        .login-logo {
            width: 260px;
            max-width: 80%;
            height: auto;
        }
        .login-card {
            width: 100%;
            background: rgba(255,255,255,.96);
            border-top: 4px solid #9b78b6;
            border-radius: 2px;
            box-shadow: 0 10px 24px rgba(77,46,98,.20);
            padding: 20px 18px;
        }
        .btn-login {
            background: #8764a8;
            color: #fff;
            border: 1px solid #8764a8;
            border-radius: 8px;
            padding: 10px 18px;
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1;
            min-width: 120px;
            transition: all .2s ease;
            box-shadow: 0 4px 10px rgba(77,46,98,.2);
        }
        .btn-login:hover {
            background: #775497;
            border-color: #775497;
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(77,46,98,.24);
        }
        .btn-login:focus-visible {
            outline: 0;
            box-shadow: 0 0 0 3px rgba(198,172,219,.7), 0 8px 16px rgba(77,46,98,.24);
        }
        .btn-register {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #b290c3;
            color: #5e4580;
            background: #fff;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: .95rem;
            font-weight: 600;
            text-decoration: none;
            transition: all .2s ease;
        }
        .btn-register:hover {
            background: #f7f2fb;
            border-color: #9b78b6;
            color: #4f376f;
        }
    </style>
</head>
<body>
    <main class="login-bg">
        <div class="login-wrap">
            <img src="{{ asset('imagenes/logo_booty.svg') }}" alt="Booty Fitness" class="login-logo">

            <div class="login-card">
                <div>
                    <p class="mb-5 text-center text-2xl font-bold text-[#2d210d]">Inicia sesión para continuar</p>

                    <form method="POST" action="{{ url('/login') }}" class="space-y-4">
                        @csrf

                        <div>
                            <div class="flex overflow-hidden rounded-md border border-slate-300 bg-white">
                                <input type="email"
                                       name="email"
                                       value="{{ old('email') }}"
                                       placeholder="Correo electrónico"
                                       class="w-full border-0 px-3 py-2.5 text-base focus:ring-0">
                                <span class="inline-flex w-10 items-center justify-center bg-[#8a67ab] text-white">
                                    <i class="fas fa-envelope text-sm"></i>
                                </span>
                            </div>
                            @error('email')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="flex overflow-hidden rounded-md border border-slate-300 bg-white">
                                <input type="password"
                                       name="password"
                                       placeholder="Contraseña"
                                       class="w-full border-0 px-3 py-2.5 text-base focus:ring-0">
                                <span class="inline-flex w-10 items-center justify-center bg-[#8a67ab] text-white">
                                    <i class="fas fa-lock text-sm"></i>
                                </span>
                            </div>
                            @error('password')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <label for="remember" class="inline-flex items-center gap-2 text-base font-semibold text-[#2d210d]">
                                <input id="remember" type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-[#8a67ab] focus:ring-[#d6c0e3]">
                                Recordarme
                            </label>

                            <button type="submit" class="btn-login">
                                Ingresar
                            </button>
                        </div>
                    </form>

                    <div class="mt-5">
                        <a href="{{ route('register') }}" class="btn-register">Registrar nueva cuenta</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
