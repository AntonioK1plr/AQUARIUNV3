import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Register({ continueToCart = false }) {
    const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

    function submit(event) {
        event.preventDefault();
        form.post('/registro');
    }

    return (
        <div className="min-h-screen bg-slate-50 text-[#0b192c]">
            <Head title="Crear cuenta · AQUARIUM" />
            <header className="bg-[#0b192c] px-5 py-5 text-white"><a href="/" className="font-black tracking-wide">AQUARIUM</a></header>
            <main className="mx-auto grid min-h-[78vh] max-w-5xl items-center gap-8 px-5 py-12 md:grid-cols-2">
                <section className="rounded-3xl bg-[#0b192c] p-8 text-white sm:p-10">
                    <p className="text-xs font-bold uppercase tracking-[0.16em] text-[#08c2cd]">Acuarismo responsable</p>
                    <h1 className="mt-4 text-3xl font-black">Únete a AQUARIUM</h1>
                    <p className="mt-4 text-sm leading-6 text-slate-300">Crea tu cuenta de cliente para dar seguimiento a tus compras y servicios.</p>
                </section>
                <form onSubmit={submit} className="rounded-3xl bg-white p-7 shadow-sm ring-1 ring-slate-900/10 sm:p-9">
                    <h2 className="text-2xl font-extrabold">Crear cuenta</h2>
                    <label className="mt-6 block text-sm font-semibold">Nombre completo
                        <input type="text" autoComplete="name" required maxLength="120" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} className="mt-2 w-full rounded-xl border-2 border-[#08aeb8] bg-white shadow-[0_2px_8px_rgba(8,174,184,0.12)] focus:border-teal-700 focus:ring-4 focus:ring-teal-100" />
                    </label>
                    {form.errors.name && <p className="mt-1 text-sm text-rose-700">{form.errors.name}</p>}
                    <label className="mt-4 block text-sm font-semibold">Correo electrónico
                        <input type="email" autoComplete="email" required value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} className="mt-2 w-full rounded-xl border-2 border-[#3974b8] bg-white shadow-[0_2px_8px_rgba(57,116,184,0.12)] focus:border-blue-700 focus:ring-4 focus:ring-blue-100" />
                    </label>
                    {form.errors.email && <p className="mt-1 text-sm text-rose-700">{form.errors.email}</p>}
                    <label className="mt-4 block text-sm font-semibold">Contraseña
                        <input type="password" autoComplete="new-password" required value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} className="mt-2 w-full rounded-xl border-2 border-[#526b86] bg-white shadow-[0_2px_8px_rgba(11,25,44,0.12)] focus:border-[#0b192c] focus:ring-4 focus:ring-blue-100" />
                    </label>
                    {form.errors.password && <p className="mt-1 text-sm text-rose-700">{form.errors.password}</p>}
                    <p className="mt-1 text-xs text-slate-500">Mínimo 10 caracteres, con mayúsculas, minúsculas y al menos un número.</p>
                    <label className="mt-4 block text-sm font-semibold">Confirmar contraseña
                        <input type="password" autoComplete="new-password" required value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} className="mt-2 w-full rounded-xl border-2 border-[#087d87] bg-white shadow-[0_2px_8px_rgba(8,125,135,0.12)] focus:border-teal-800 focus:ring-4 focus:ring-teal-100" />
                    </label>
                    <button disabled={form.processing} className="mt-6 w-full rounded-xl bg-[#0b192c] px-4 py-3 font-bold text-white hover:bg-[#173555] disabled:opacity-60">{form.processing ? 'Creando cuenta…' : 'Crear cuenta'}</button>
                    <p className="mt-5 text-center text-sm text-slate-600">¿Ya tienes cuenta? <Link href={continueToCart ? '/acceso?continuar=carrito' : '/acceso'} className="font-bold text-teal-800 hover:underline">Iniciar sesión</Link></p>
                </form>
            </main>
        </div>
    );
}
