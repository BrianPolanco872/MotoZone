<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso al panel · {{ config('app.name') }}</title>
    <script id="pos-theme-reset">
        try {
            sessionStorage.removeItem('motozone.pos.theme');
        } catch {
            // Si el almacenamiento no está disponible, el panel arranca en modo Día.
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .login-satin {
            background: radial-gradient(circle at 50% 45%, #ffffff 0%, #f7f2ea 40%, #ede3d1 75%, #dfd0bb 100%);
        }

        .login-vignette {
            background: radial-gradient(circle at 50% 50%, rgb(255 255 255 / 20%) 0%, rgb(223 208 187 / 45%) 80%, rgb(184 160 126 / 35%) 100%);
            mix-blend-mode: soft-light;
        }

        @keyframes login-sheen {
            0%, 100% { opacity: 0.55; filter: blur(60px); }
            50% { opacity: 0.85; filter: blur(48px); }
        }

        .login-sheen {
            animation: login-sheen 14s ease-in-out infinite;
            will-change: opacity, filter;
        }

        @media (prefers-reduced-motion: reduce) {
            .login-sheen {
                animation: none;
            }
        }
    </style>
</head>
<body class="login-satin relative min-h-screen overflow-x-hidden font-sans text-[#0F172A] antialiased selection:bg-[#EA580C]/20 selection:text-[#EA580C]">
    <div class="absolute inset-x-0 top-0 z-20 flex items-center justify-between px-6 py-4 text-[11px] font-semibold tracking-wider text-[#8A7963] uppercase select-none sm:px-10">
        <div class="flex items-center gap-2">
            <span class="inline-block size-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="tracking-widest text-[#71614C]">MotoZone Core · Red operativa</span>
        </div>
        <div class="hidden items-center gap-1.5 rounded-full border border-[#E2D5C3]/80 bg-white/40 px-3 py-1 text-[#66543D] shadow-sm backdrop-blur-md sm:inline-flex">
            <x-icon name="map-pin" class="size-3.5 text-[#B89A72]" />
            <span>MotoZone MX</span>
        </div>
    </div>

    <canvas id="login-satin-canvas" class="pointer-events-none fixed inset-0 z-0 size-full opacity-90" aria-hidden="true"></canvas>

    <div class="pointer-events-none fixed inset-0 z-[1] overflow-hidden login-vignette" aria-hidden="true">
        <div class="login-sheen absolute top-1/2 left-1/2 size-[750px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[radial-gradient(circle,#ffffff_0%,rgb(247_239_227/70%)_45%,transparent_75%)]"></div>
    </div>

    <main class="relative z-10 flex min-h-screen w-full flex-col items-center justify-center overflow-hidden px-4 pt-16 pb-12">
        <div class="relative flex w-full max-w-[440px] flex-col items-center">
            <header class="mb-6 flex flex-col items-center text-center">
                <a href="{{ route('home') }}" class="group mb-4 flex items-center gap-3 transition-transform duration-300 hover:scale-105" aria-label="{{ config('app.name') }} · Inicio">
                    <span class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-600/25 ring-1 ring-white/60">
                        <x-icon name="moto" class="size-6" />
                    </span>
                    <span class="font-display text-2xl font-black tracking-tight drop-shadow-sm">
                        <span class="text-[#1E293B]">Moto</span><span class="text-[#EA580C]">Zone</span>
                    </span>
                </a>
                <h1 class="text-2xl font-bold tracking-tight text-[#0F172A] uppercase drop-shadow-sm sm:text-[26px]">Acceso al panel</h1>
                <p class="mt-1.5 max-w-sm text-sm leading-relaxed font-medium text-[#524433]">Inicia sesión para administrar los productos o abrir el punto de venta.</p>
            </header>

            <section aria-label="Formulario de inicio de sesión" class="w-full rounded-2xl border border-stone-200/90 bg-white/95 p-7 shadow-[0_20px_50px_rgba(154,134,107,0.22)] ring-1 ring-black/5 backdrop-blur-xl sm:p-8">
                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                        <ul class="list-disc space-y-1 pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('login.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="mb-2 block text-xs font-semibold tracking-wider text-[#0F172A] uppercase">Nombre completo</label>
                        <div class="relative">
                            <x-icon name="user" class="pointer-events-none absolute inset-y-0 left-0 my-auto ml-3.5 size-4 text-stone-400" />
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Ana Ramírez" class="block w-full rounded-xl border border-[#CBD5E1] bg-[#F8FAFC] py-2.5 pr-4 pl-10 text-sm text-[#0F172A] transition-colors placeholder:text-stone-400 focus:border-[#EA580C] focus:bg-white focus:ring-2 focus:ring-[#EA580C]/20 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="mb-2 block text-xs font-semibold tracking-wider text-[#0F172A] uppercase">Correo electrónico</label>
                        <div class="relative">
                            <x-icon name="mail" class="pointer-events-none absolute inset-y-0 left-0 my-auto ml-3.5 size-4 text-stone-400" />
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" placeholder="admin@motozone.com" class="block w-full rounded-xl border border-[#CBD5E1] bg-[#F8FAFC] py-2.5 pr-4 pl-10 text-sm text-[#0F172A] transition-colors placeholder:text-stone-400 focus:border-[#EA580C] focus:bg-white focus:ring-2 focus:ring-[#EA580C]/20 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-xs font-semibold tracking-wider text-[#0F172A] uppercase">Contraseña</label>
                        <div class="relative">
                            <x-icon name="lock" class="pointer-events-none absolute inset-y-0 left-0 my-auto ml-3.5 size-4 text-stone-400" />
                            <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="••••••••" class="block w-full rounded-xl border border-[#CBD5E1] bg-[#F8FAFC] py-2.5 pr-11 pl-10 text-sm text-[#0F172A] transition-colors placeholder:text-stone-400 focus:border-[#EA580C] focus:bg-white focus:ring-2 focus:ring-[#EA580C]/20 focus:outline-none">
                            <button id="toggle-password" type="button" aria-label="Mostrar u ocultar contraseña" aria-pressed="false" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-stone-400 transition-colors hover:text-stone-600 focus:outline-none">
                                <x-icon name="eye" id="eye-open" class="size-4" />
                                <x-icon name="eye-off" id="eye-off" class="hidden size-4" />
                            </button>
                        </div>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2.5 pt-0.5 text-sm text-[#475569] select-none">
                        <input type="checkbox" name="remember" value="1" class="size-4 cursor-pointer rounded border-stone-300 bg-white accent-[#EA580C]">
                        Mantener mi sesión iniciada
                    </label>

                    <div class="pt-1">
                        <button type="submit" class="group flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#EA580C] via-[#E14E07] to-[#DC2626] px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-[#EA580C]/30 transition-all duration-150 hover:-translate-y-0.5 hover:shadow-[#EA580C]/45 focus:ring-2 focus:ring-[#EA580C] focus:ring-offset-2 focus:ring-offset-white focus:outline-none active:translate-y-0">
                            <span>Entrar</span>
                            <span aria-hidden="true" class="transition-transform duration-150 group-hover:translate-x-1">→</span>
                        </button>
                    </div>

                    <div class="pt-2 text-center">
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-stone-400">
                            <x-icon name="shield" class="size-3.5 text-stone-400" />
                            Acceso seguro
                        </span>
                    </div>
                </form>
            </section>

            <footer class="mt-6 flex flex-col items-center gap-3">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-full border border-[#D5C7B3] bg-white/80 px-5 py-2 text-xs font-medium text-[#574836] shadow-sm backdrop-blur-md transition-all duration-150 hover:border-stone-400 hover:bg-white hover:text-[#0F172A]">
                    <span aria-hidden="true">←</span>
                    <span>Volver a la tienda</span>
                </a>
                <p class="text-[11px] text-[#8C7A65]">© {{ now()->year }} {{ config('app.name') }} Parts &amp; Accessories. Todos los derechos reservados.</p>
            </footer>
        </div>
    </main>

    <script>
        (() => {
            const canvas = document.getElementById('login-satin-canvas');

            if (!canvas) {
                return;
            }

            const ctx = canvas.getContext('2d');
            const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const particles = [];
            let width = 0;
            let height = 0;

            const resize = () => {
                width = canvas.width = window.innerWidth;
                height = canvas.height = window.innerHeight;
            };

            window.addEventListener('resize', resize);
            resize();

            for (let i = 0; i < 42; i++) {
                particles.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    size: Math.random() * 2.2 + 0.8,
                    speedY: -(Math.random() * 0.35 + 0.15),
                    speedX: (Math.random() - 0.5) * 0.25,
                    alpha: Math.random() * 0.5 + 0.2,
                    pulseSpeed: Math.random() * 0.03 + 0.015,
                    pulse: Math.random() * Math.PI * 2,
                });
            }

            const drawSilk = (time) => {
                ctx.clearRect(0, 0, width, height);

                const background = ctx.createRadialGradient(width * 0.5, height * 0.45, 50, width * 0.5, height * 0.5, Math.max(width, height) * 0.8);
                background.addColorStop(0, '#FFFDF9');
                background.addColorStop(0.35, '#F9F4EB');
                background.addColorStop(0.7, '#EFE3D0');
                background.addColorStop(1, '#DFCDB6');
                ctx.fillStyle = background;
                ctx.fillRect(0, 0, width, height);

                for (let i = 0; i < 5; i++) {
                    const phase = time * 0.0006 + i * 1.8;
                    const yOffset = height * (0.2 + i * 0.16);

                    ctx.beginPath();
                    ctx.moveTo(-50, height + 100);
                    ctx.lineTo(-50, yOffset);

                    for (let x = 0; x <= width + 60; x += 40) {
                        const y = yOffset
                            + Math.sin(x * 0.0022 + phase) * (65 + i * 12)
                            + Math.cos(x * 0.0045 - phase * 0.8) * 35
                            + Math.sin((x + yOffset) * 0.0015 + phase * 1.2) * 20;

                        ctx.lineTo(x, y);
                    }

                    ctx.lineTo(width + 50, height + 100);
                    ctx.closePath();

                    const ribbon = ctx.createLinearGradient(0, yOffset - 80, width, yOffset + 180);

                    if (i % 2 === 0) {
                        ribbon.addColorStop(0, 'rgba(255, 255, 255, 0.4)');
                        ribbon.addColorStop(0.4, 'rgba(235, 218, 194, 0.22)');
                        ribbon.addColorStop(0.75, 'rgba(196, 172, 140, 0.15)');
                        ribbon.addColorStop(1, 'rgba(255, 255, 255, 0.45)');
                    } else {
                        ribbon.addColorStop(0, 'rgba(215, 192, 160, 0.18)');
                        ribbon.addColorStop(0.35, 'rgba(255, 255, 255, 0.55)');
                        ribbon.addColorStop(0.7, 'rgba(238, 224, 203, 0.2)');
                        ribbon.addColorStop(1, 'rgba(200, 176, 144, 0.14)');
                    }

                    ctx.fillStyle = ribbon;
                    ctx.fill();
                }

                ctx.save();
                ctx.globalCompositeOperation = 'soft-light';

                for (let j = 0; j < 3; j++) {
                    const angle = time * 0.0004 + j * 2.1;
                    const cx = width * (0.3 + j * 0.25) + Math.cos(angle) * 80;
                    const cy = height * (0.4 + j * 0.2) + Math.sin(angle) * 60;
                    const radius = Math.min(width, height) * (0.45 + j * 0.15);
                    const fold = ctx.createRadialGradient(cx, cy, 10, cx, cy, radius);

                    fold.addColorStop(0, 'rgba(255, 255, 255, 0.7)');
                    fold.addColorStop(0.5, 'rgba(226, 205, 177, 0.35)');
                    fold.addColorStop(1, 'rgba(180, 154, 120, 0)');

                    ctx.fillStyle = fold;
                    ctx.beginPath();
                    ctx.ellipse(cx, cy, radius * 1.2, radius * 0.7, Math.PI / 4 + Math.sin(angle) * 0.2, 0, Math.PI * 2);
                    ctx.fill();
                }

                ctx.restore();
                ctx.save();

                for (const particle of particles) {
                    if (!prefersReduced) {
                        particle.y += particle.speedY;
                        particle.x += particle.speedX;
                        particle.pulse += particle.pulseSpeed;

                        if (particle.y < -10) particle.y = height + 10;
                        if (particle.x < -10) particle.x = width + 10;
                        if (particle.x > width + 10) particle.x = -10;
                    }

                    const alpha = particle.alpha * (0.6 + Math.sin(particle.pulse) * 0.4);

                    ctx.beginPath();
                    ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(202, 160, 102, ${alpha})`;
                    ctx.fill();

                    ctx.beginPath();
                    ctx.arc(particle.x, particle.y, particle.size * 2.6, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(255, 235, 195, ${alpha * 0.45})`;
                    ctx.fill();
                }

                ctx.restore();
            };

            if (prefersReduced) {
                drawSilk(1000);
            } else {
                const loop = (timestamp) => {
                    drawSilk(timestamp);
                    requestAnimationFrame(loop);
                };

                requestAnimationFrame(loop);
            }
        })();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.getElementById('toggle-password');
            const password = document.getElementById('password');
            const eyeOpen = document.getElementById('eye-open');
            const eyeOff = document.getElementById('eye-off');

            if (!toggle || !password || !eyeOpen || !eyeOff) {
                return;
            }

            toggle.addEventListener('click', () => {
                const reveal = password.type === 'password';

                password.type = reveal ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', String(reveal));
                eyeOpen.classList.toggle('hidden', reveal);
                eyeOff.classList.toggle('hidden', !reveal);
            });
        });
    </script>
</body>
</html>
