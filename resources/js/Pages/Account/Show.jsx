import React from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';

const roleLabels = {
    cliente: 'Cliente',
    administrador: 'Administrador',
    almacenista: 'Almacenista',
    cajero_pos: 'Cajero POS',
    veterinario: 'Veterinario',
};

const staffShortcuts = {
    administrador: [
        { href: '/admin', label: 'Panel de administración', detail: 'Resumen y accesos de operación' },
        { href: '/admin/empleados', label: 'Gestión de empleados', detail: 'Roles, cuentas y datos del equipo' },
        { href: '/almacen', label: 'Inventario', detail: 'Existencias, lotes y movimientos' },
        { href: '/pos', label: 'Punto de venta', detail: 'Ventas de mostrador, entregas y corte' },
    ],
    almacenista: [
        { href: '/almacen', label: 'Control de inventario', detail: 'Productos, existencias y lotes' },
        { href: '/almacen/picking', label: 'Preparación de pedidos', detail: 'Picking y despacho de pedidos' },
    ],
    cajero_pos: [
        { href: '/pos', label: 'Abrir caja POS', detail: 'Registrar una venta de mostrador' },
        { href: '/pos/recolecciones', label: 'Entregar pedidos', detail: 'Validar código y registrar entrega' },
        { href: '/pos/cierre-z', label: 'Cierre de caja', detail: 'Consultar y generar el corte diario' },
    ],
    veterinario: [
        { href: '/veterinaria/citas', label: 'Agenda clínica', detail: 'Citas, pacientes y datos de contacto' },
    ],
};

export default function AccountShow({ user, clinicalRecords = null }) {
    const form = useForm({ name: user.name || '', phone: user.phone || '', address: user.address || '' });
    const { props } = usePage();

    function saveProfile(event) {
        event.preventDefault();
        form.patch('/cuenta');
    }

    return (
        <div className="min-h-screen bg-slate-50 text-[#0b192c]">
            <Head title="Mi cuenta · AQUARIUM" />
            <header className="bg-[#0b192c] px-5 py-5 text-white"><div className="mx-auto flex max-w-5xl items-center justify-between"><a href="/" className="font-black tracking-wide">AQUARIUM</a><button onClick={() => router.post('/salir')} className="text-sm font-semibold text-slate-200 hover:text-white">Cerrar sesión</button></div></header>
            <main className="mx-auto max-w-5xl px-5 py-10">
                <p className="text-xs font-bold uppercase tracking-[0.14em] text-teal-700">Cuenta de usuario</p>
                <h1 className="mt-2 text-3xl font-black">Hola, {user.name}</h1>
                <section className="mt-7 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/10">
                    <h2 className="text-lg font-extrabold">Datos de la cuenta</h2>
                    <dl className="mt-5 grid gap-4 sm:grid-cols-2">
                        <div><dt className="text-xs font-bold uppercase tracking-wide text-slate-500">Nombre</dt><dd className="mt-1 font-semibold">{user.name}</dd></div>
                        <div><dt className="text-xs font-bold uppercase tracking-wide text-slate-500">Correo</dt><dd className="mt-1 font-semibold">{user.email}</dd></div>
                        <div><dt className="text-xs font-bold uppercase tracking-wide text-slate-500">Tipo de cuenta</dt><dd className="mt-1 font-semibold">{roleLabels[user.role] ?? user.role}</dd></div>
                    </dl>
                </section>
                <section className="mt-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/10">
                    <h2 className="text-lg font-extrabold">Editar perfil</h2>
                    <p className="mt-1 text-sm text-slate-600">Actualiza tu nombre, teléfono y dirección de contacto.</p>
                    {props.flash?.success && <p role="status" className="mt-4 rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">{props.flash.success}</p>}
                    <form onSubmit={saveProfile} className="mt-5 grid gap-4 sm:grid-cols-2">
                        <label className="text-sm font-semibold">Nombre<input required maxLength="120" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} className="mt-2 w-full rounded-xl border-slate-300" />{form.errors.name && <span className="mt-1 block text-xs text-rose-700">{form.errors.name}</span>}</label>
                        <label className="text-sm font-semibold">Teléfono<input type="tel" maxLength="30" value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} className="mt-2 w-full rounded-xl border-slate-300" />{form.errors.phone && <span className="mt-1 block text-xs text-rose-700">{form.errors.phone}</span>}</label>
                        <label className="text-sm font-semibold sm:col-span-2">Dirección<textarea rows="3" maxLength="1000" value={form.data.address} onChange={(event) => form.setData('address', event.target.value)} className="mt-2 w-full rounded-xl border-slate-300" />{form.errors.address && <span className="mt-1 block text-xs text-rose-700">{form.errors.address}</span>}</label>
                        <button disabled={form.processing} className="rounded-xl bg-[#0b192c] px-4 py-3 text-sm font-bold text-white hover:bg-[#173555] disabled:opacity-60 sm:col-span-2">Guardar cambios</button>
                    </form>
                </section>
                {user.role === 'cliente' && <div className="mt-5 flex flex-wrap gap-3"><a href="/citas" className="inline-flex rounded-xl bg-[#08c2cd] px-4 py-3 text-sm font-extrabold text-[#0b192c] hover:bg-[#20d5dc]">Ver y agendar citas</a><a href="/cuenta/pedidos" className="inline-flex rounded-xl bg-[#0b192c] px-4 py-3 text-sm font-bold text-white hover:bg-[#173555]">Mis pedidos</a><a href="/catalogo" className="inline-flex rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-100">Explorar catálogo</a></div>}
                {user.role === 'cliente' && <section className="mt-7 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/10">
                    <div className="flex flex-wrap items-end justify-between gap-3"><div><p className="text-xs font-extrabold uppercase tracking-[0.14em] text-teal-700">Atención clínica</p><h2 className="mt-1 text-xl font-black">Mis recetas y expedientes</h2><p className="mt-1 text-sm text-slate-600">Los documentos de consultas atendidas se guardan en esta cuenta.</p></div>{clinicalRecords?.total > 0 && <span className="rounded-full bg-teal-50 px-3 py-1.5 text-xs font-bold text-teal-800">{clinicalRecords.total} {clinicalRecords.total === 1 ? 'documento' : 'documentos'}</span>}</div>
                    <p className="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-600">Los expedientes son de solo lectura para el cliente. Si necesitas corregir algún dato, solicita la actualización a la clínica.</p>
                    {clinicalRecords?.data?.length ? <div className="mt-4 space-y-3">{clinicalRecords.data.map((record) => <article key={record.id} className="rounded-xl border border-slate-200 p-4 sm:p-5">
                        <div className="flex flex-wrap items-start justify-between gap-3"><div><p className="text-xs font-bold uppercase tracking-wide text-teal-700">Consulta del {new Date(record.scheduled_at).toLocaleDateString('es-MX', { dateStyle: 'long' })}</p><h3 className="mt-1 text-base font-extrabold">{record.patient_name} <span className="font-medium text-slate-500">· {record.species}</span></h3><p className="mt-1 text-xs text-slate-500">Atendió: {record.veterinarian?.name || 'Clínica AQUARIUM'}</p></div><a href={`/expedientes/clinicos/${record.id}.pdf`} className="inline-flex shrink-0 items-center gap-2 rounded-lg bg-[#0b192c] px-4 py-2.5 text-xs font-extrabold text-white transition hover:bg-[#173555]">Descargar receta PDF <span aria-hidden="true">↓</span></a></div>
                        <div className="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2"><div><p className="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Diagnóstico</p><p className="mt-1 whitespace-pre-wrap text-sm leading-5 text-slate-700">{record.diagnosis}</p></div><div><p className="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Prescripción e indicaciones</p><p className="mt-1 whitespace-pre-wrap text-sm leading-5 text-slate-700">{record.prescription || 'No se registraron indicaciones adicionales.'}</p>{record.clinical_data?.medication && <p className="mt-2 text-xs leading-5 text-slate-600"><b>{record.clinical_data.medication}</b>{record.clinical_data.dose ? ` · ${record.clinical_data.dose}` : ''}{record.clinical_data.frequency ? ` · ${record.clinical_data.frequency}` : ''}{record.clinical_data.duration_days ? ` · ${record.clinical_data.duration_days} días` : ''}</p>}</div></div>
                    </article>)}</div> : <div className="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center"><p className="font-bold text-slate-700">Aún no tienes recetas o expedientes disponibles.</p><p className="mt-1 text-sm text-slate-500">Aparecerán aquí cuando el veterinario complete una consulta.</p></div>}
                    {clinicalRecords?.links?.length > 3 && <nav className="mt-5 flex flex-wrap justify-center gap-2" aria-label="Paginación de expedientes">{clinicalRecords.links.map((link, index) => <Link key={index} href={link.url || '#'} preserveScroll className={`rounded-lg border px-3 py-2 text-sm ${link.active ? 'border-[#0b192c] bg-[#0b192c] text-white' : 'border-slate-300 bg-white text-slate-700'} ${!link.url ? 'pointer-events-none opacity-40' : ''}`} dangerouslySetInnerHTML={{ __html: link.label }} />)}</nav>}
                </section>}
                {staffShortcuts[user.role] && <section className="mt-7"><div><p className="text-xs font-extrabold uppercase tracking-[0.14em] text-teal-700">Herramientas de trabajo</p><h2 className="mt-1 text-xl font-black">Acceso a tus funciones</h2><p className="mt-1 text-sm text-slate-600">Selecciona el módulo que corresponde a tus tareas.</p></div><div className="mt-4 grid gap-3 sm:grid-cols-2">{staffShortcuts[user.role].map((item) => <a key={item.href} href={item.href} className="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-teal-300 hover:shadow-sm"><span className="text-sm font-extrabold text-[#0b192c]">{item.label} <span className="text-teal-700">→</span></span><span className="mt-1 block text-xs leading-5 text-slate-500">{item.detail}</span></a>)}</div></section>}
                <a href="/" className="mt-6 inline-block text-sm font-bold text-teal-800 hover:underline">← Regresar al inicio</a>
            </main>
        </div>
    );
}
