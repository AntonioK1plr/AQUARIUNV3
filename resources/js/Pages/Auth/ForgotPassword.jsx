import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function ForgotPassword() {
    const form = useForm({ email: '' });
    const { props } = usePage();

    function submit(event) {
        event.preventDefault();
        form.post('/recuperar-contrasena');
    }

    return <div className="min-h-screen bg-slate-50 px-5 text-[#0b192c]"><Head title="Recuperar contraseña · AQUARIUM" /><header className="mx-auto max-w-5xl py-6"><Link href="/" className="font-black tracking-wide">AQUARIUM</Link></header><main className="mx-auto mt-12 max-w-lg rounded-3xl bg-white p-7 shadow-sm ring-1 ring-slate-900/10 sm:p-9"><p className="text-xs font-bold uppercase tracking-[0.15em] text-teal-700">Seguridad de la cuenta</p><h1 className="mt-2 text-2xl font-black">Recupera tu acceso</h1><p className="mt-2 text-sm leading-6 text-slate-600">Enviaremos un enlace temporal. En este entorno local, el mensaje queda en los logs de correo de desarrollo y no se envía por un servicio externo.</p>{props.flash?.success && <p role="status" className="mt-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{props.flash.success}</p>}{props.flash?.error && <p role="alert" className="mt-4 rounded-xl bg-rose-50 p-3 text-sm text-rose-800">{props.flash.error}</p>}<form onSubmit={submit} className="mt-6"><label className="block text-sm font-semibold">Correo de la cuenta<input type="email" required autoComplete="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} className="mt-2 w-full rounded-xl border-2 border-[#08aeb8] bg-white shadow-[0_2px_8px_rgba(8,174,184,0.12)] focus:border-teal-700 focus:ring-4 focus:ring-teal-100" /></label>{form.errors.email && <p className="mt-1 text-sm text-rose-700">{form.errors.email}</p>}<button disabled={form.processing} className="mt-5 w-full rounded-xl bg-[#0b192c] px-4 py-3 font-bold text-white disabled:opacity-60">Enviar enlace local</button></form><Link href="/acceso" className="mt-5 block text-center text-sm font-bold text-teal-800 hover:underline">Volver al acceso</Link></main></div>;
}
