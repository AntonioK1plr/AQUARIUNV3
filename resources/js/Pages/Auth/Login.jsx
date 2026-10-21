import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ continueToCart = false }) {
    const form = useForm({ email: '', password: '', remember: false });

    function submit(event) {
        event.preventDefault();
        form.post('/acceso');
    }

    return (
        <div className="min-h-screen bg-slate-50 text-[#0b192c]">
            <Head title="Iniciar sesión · AQUARIUM" />
            <header className="bg-[#0b192c] px-5 py-5 text-white"><a href="/" className="font-black tracking-wide">AQUARIUM</a></header>
            <main className="mx-auto grid min-h-[78vh] max-w-5xl items-center gap-8 px-5 py-12 md:grid-cols-2">
                <section className="rounded-3xl bg-[#0b192c] p-8 text-white sm:p-10">
                    <p className="text-xs font-bold uppercase tracking-[0.16em] text-[#08c2cd]">Portal de clientes</p>
                    <h1 className="mt-4 text-3xl font-black">Bienvenido a AQUARIUM</h1>
                    <p className="mt-4 text-sm leading-6 text-slate-300">Accede a tu cuenta para continuar con los servicios y funciones del sistema.</p>
                </section>
                <form onSubmit={submit} className="rounded-3xl bg-white p-7 shadow-sm ring-1 ring-slate-900/10 sm:p-9">
                    <h2 className="text-2xl font-extrabold">Iniciar sesión</h2>
                    <label className="mt-6 block text-sm font-semibold">Correo electrónico
                        <input type="email" autoComplete="email" required value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} className="mt-2 w-full rounded-xl border-2 border-[#08aeb8] bg-white shadow-[0_2px_8px_rgba(8,174,184,0.12)] placeholder:text-slate-400 focus:border-teal-700 focus:ring-4 focus:ring-teal-100" />
                    </label>
                    {form.errors.email && <p className="mt-1 text-sm text-rose-700">{form.errors.email}</p>}
                    <label className="mt-4 block text-sm font-semibold">Contraseña
                        <input type="password" autoComplete="current-password" required value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} className="mt-2 w-full rounded-xl border-2 border-[#526b86] bg-white shadow-[0_2px_8px_rgba(11,25,44,0.12)] placeholder:text-slate-400 focus:border-[#0b192c] focus:ring-4 focus:ring-blue-100" />
                    </label>
                    {form.errors.password && <p className="mt-1 text-sm text-rose-700">{form.errors.password}</p>}
                    <p className="mt-2 text-right text-sm"><Link href="/recuperar-contrasena" className="font-bold text-teal-800 hover:underline">¿Olvidaste tu contraseña?</Link></p>
                    <label className="mt-4 flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" checked={form.data.remember} onChange={(event) => form.setData('remember', event.target.checked)} className="rounded border-slate-300 text-teal-700" /> Recordarme</label>
                    <button disabled={form.processing} className="mt-6 w-full rounded-xl bg-[#0b192c] px-4 py-3 font-bold text-white hover:bg-[#173555] disabled:opacity-60">{form.processing ? 'Validando…' : 'Entrar'}</button>
                    <p className="mt-5 text-center text-sm text-slate-600">¿Aún no tienes cuenta? <Link href={continueToCart ? '/registro?continuar=carrito' : '/registro'} className="font-bold text-teal-800 hover:underline">Crear cuenta</Link></p>
                </form>
            </main>
        </div>
    );
}
