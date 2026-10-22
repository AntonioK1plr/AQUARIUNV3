import React, { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import OperationsLayout from '../../../Layouts/OperationsLayout';

const roleNames = {
    administrador: 'Administrador', almacenista: 'Almacenista', cajero_pos: 'Cajero POS', veterinario: 'Veterinario',
};

function Metric({ label, value, accent }) {
    return <article className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-900/[0.06]"><p className="text-xs font-bold uppercase tracking-wider text-slate-500">{label}</p><p className={`mt-3 text-3xl font-black tracking-tight ${accent}`}>{value}</p></article>;
}

function Pagination({ links }) {
    if (!links?.length || links.length < 4) return null;
    return <nav className="flex flex-wrap gap-1" aria-label="Paginación">{links.map((link, index) => <Link key={`${link.label}-${index}`} href={link.url || '#'} preserveScroll preserveState className={`min-w-9 rounded-lg px-3 py-2 text-center text-xs font-bold ${link.active ? 'bg-[#0b192c] text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'} ${!link.url ? 'pointer-events-none opacity-40' : ''}`} dangerouslySetInnerHTML={{ __html: link.label }} />)}</nav>;
}

function EmployeeDialog({ roles, onClose }) {
    const form = useForm({ name: '', email: '', phone: '', role: 'almacenista', password: '', password_confirmation: '' });
    function submit(event) {
        event.preventDefault();
        form.post('/admin/empleados', { onSuccess: () => onClose() });
    }
    return (
        <div className="fixed inset-0 z-50 grid place-items-center bg-[#071321]/65 p-4 backdrop-blur-sm" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget) onClose(); }}>
            <section role="dialog" aria-modal="true" aria-labelledby="employee-dialog-title" className="max-h-[92vh] w-full max-w-xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
                <div className="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 sm:px-8"><div><p className="text-[10px] font-extrabold uppercase tracking-[0.16em] text-teal-700">Alta controlada</p><h2 id="employee-dialog-title" className="mt-1 text-xl font-black">Registrar empleado</h2></div><button type="button" onClick={onClose} aria-label="Cerrar" className="rounded-full bg-slate-100 px-3 py-1 text-xl leading-6 text-slate-500 hover:bg-slate-200">×</button></div>
                <form onSubmit={submit} className="space-y-4 px-6 py-6 sm:px-8">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <label className="text-xs font-bold text-slate-700">Nombre completo<input value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} className="mt-1.5 w-full rounded-xl border-slate-300 text-sm font-normal" maxLength="120" required />{form.errors.name && <small className="mt-1 block text-rose-700">{form.errors.name}</small>}</label>
                        <label className="text-xs font-bold text-slate-700">Rol<select value={form.data.role} onChange={(event) => form.setData('role', event.target.value)} className="mt-1.5 w-full rounded-xl border-slate-300 text-sm font-normal">{roles.map((role) => <option key={role.value} value={role.value}>{role.label}</option>)}</select>{form.errors.role && <small className="mt-1 block text-rose-700">{form.errors.role}</small>}</label>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <label className="text-xs font-bold text-slate-700">Correo electrónico<input type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} className="mt-1.5 w-full rounded-xl border-slate-300 text-sm font-normal" required />{form.errors.email && <small className="mt-1 block text-rose-700">{form.errors.email}</small>}</label>
                        <label className="text-xs font-bold text-slate-700">Teléfono<input type="tel" value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} className="mt-1.5 w-full rounded-xl border-slate-300 text-sm font-normal" maxLength="30" />{form.errors.phone && <small className="mt-1 block text-rose-700">{form.errors.phone}</small>}</label>
                    </div>
                    <div className="rounded-xl bg-[#f2f6f8] p-4"><p className="text-xs font-bold text-slate-800">Contraseña inicial</p><p className="mt-1 text-xs leading-5 text-slate-500">Mínimo 10 caracteres, mayúsculas, minúsculas y número.</p><div className="mt-3 grid gap-3 sm:grid-cols-2"><label className="text-xs font-semibold text-slate-700">Contraseña<input type="password" autoComplete="new-password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} className="mt-1.5 w-full rounded-lg border-slate-300 text-sm font-normal" required /></label><label className="text-xs font-semibold text-slate-700">Confirmación<input type="password" autoComplete="new-password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} className="mt-1.5 w-full rounded-lg border-slate-300 text-sm font-normal" required /></label></div>{form.errors.password && <small className="mt-1 block text-rose-700">{form.errors.password}</small>}</div>
                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-4"><button type="button" onClick={onClose} className="rounded-xl px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100">Cancelar</button><button disabled={form.processing} className="rounded-xl bg-[#0b192c] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#173555] disabled:opacity-60">{form.processing ? 'Guardando…' : 'Crear cuenta'}</button></div>
                </form>
            </section>
        </div>
    );
}

export default function EmployeeIndex({ auth, employees, filters, roleCounts, roleOptions, flash }) {
    const [dialogOpen, setDialogOpen] = useState(false);
    function submitFilters(event) {
        event.preventDefault();
        const values = Object.fromEntries(new FormData(event.currentTarget).entries());
        router.get('/admin/empleados', Object.fromEntries(Object.entries(values).filter(([, value]) => value)), { preserveState: true, preserveScroll: true, replace: true });
    }
    function updateStatus(employee) {
        router.patch(`/admin/empleados/${employee.id}/estado`, { is_active: !employee.is_active }, { preserveScroll: true });
    }
    function removeEmployee(employee) {
        if (!employee.can_delete) return;
        if (window.confirm(`¿Eliminar la cuenta de ${employee.name}? Esta acción solo está disponible si no tiene operaciones relacionadas.`)) {
            router.delete(`/admin/empleados/${employee.id}`, { preserveScroll: true });
        }
    }
    return (
        <OperationsLayout title="Personal" auth={auth} eyebrow="ADMINISTRACIÓN · PERSONAL DE SUCURSAL">
            <main className="mx-auto max-w-[1480px] px-4 py-7 sm:px-7 sm:py-10">
                <div className="flex flex-wrap items-end justify-between gap-5">
                    <div><p className="text-xs font-bold uppercase tracking-[0.15em] text-teal-700">Directorio interno</p><h1 className="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Administración de personal</h1><p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Consulta, activa o suspende cuentas y da de alta empleados. La suspensión corta el acceso y conserva el historial operativo.</p></div>
                    <button type="button" onClick={() => setDialogOpen(true)} className="inline-flex items-center gap-2 rounded-xl bg-[#08c2cd] px-5 py-3 text-sm font-extrabold text-[#0b192c] shadow-sm transition hover:bg-[#20d5dc]"><span className="text-lg leading-none">+</span> Registrar empleado</button>
                </div>
                {flash?.success && <div role="status" className="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">✓ {flash.success}</div>}
                {flash?.error && <div role="alert" className="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{flash.error}</div>}
                <section className="mt-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <Metric label="Empleados registrados" value={employees.total} accent="text-[#0b192c]" />
                    <Metric label="Administración" value={roleCounts.administrador ?? 0} accent="text-violet-700" />
                    <Metric label="Almacén y caja" value={Number(roleCounts.almacenista ?? 0) + Number(roleCounts.cajero_pos ?? 0)} accent="text-teal-700" />
                    <Metric label="Equipo veterinario" value={roleCounts.veterinario ?? 0} accent="text-sky-700" />
                </section>
                <section className="mt-7 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-900/[0.06]">
                    <div className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                        <div><h2 className="text-lg font-extrabold">Equipo de AQUARIUM</h2><p className="mt-1 text-xs text-slate-500">{employees.total} cuentas de personal</p></div>
                        <form onSubmit={submitFilters} className="flex w-full flex-wrap gap-2 sm:w-auto">
                            <label className="sr-only" htmlFor="employee-search">Buscar empleado</label><input id="employee-search" name="q" defaultValue={filters.q || ''} placeholder="Nombre, correo o teléfono" className="min-w-52 flex-1 rounded-lg border-slate-300 text-sm sm:flex-none" />
                            <label className="sr-only" htmlFor="employee-role">Filtrar por rol</label><select id="employee-role" name="role" defaultValue={filters.role || ''} className="rounded-lg border-slate-300 text-sm"><option value="">Todos los roles</option>{roleOptions.map((role) => <option key={role.value} value={role.value}>{role.label}</option>)}</select>
                            <label className="sr-only" htmlFor="employee-status">Filtrar por estado</label><select id="employee-status" name="status" defaultValue={filters.status || ''} className="rounded-lg border-slate-300 text-sm"><option value="">Todas las cuentas</option><option value="activa">Activas</option><option value="desactivada">Desactivadas</option></select>
                            <button className="rounded-lg bg-[#0b192c] px-4 py-2 text-sm font-bold text-white hover:bg-[#173555]">Filtrar</button>
                        </form>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[920px] text-left text-sm">
                            <thead className="bg-[#f5f8fa] text-[10px] font-extrabold uppercase tracking-wider text-slate-500"><tr><th className="px-6 py-3.5">Empleado</th><th className="px-4 py-3.5">Rol</th><th className="px-4 py-3.5">Correo electrónico</th><th className="px-4 py-3.5">Teléfono</th><th className="px-4 py-3.5">Estado</th><th className="px-6 py-3.5 text-right">Acciones</th></tr></thead>
                            <tbody className="divide-y divide-slate-100">
                                {employees.data.length ? employees.data.map((employee) => <tr key={employee.id} className="transition hover:bg-slate-50"><td className="px-6 py-4"><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-xl bg-[#e3f7f8] text-xs font-black uppercase text-teal-800">{employee.name.split(/\s+/).slice(0, 2).map((part) => part[0]).join('')}</span><span className="font-bold text-[#0b192c]">{employee.name}</span></div></td><td className="px-4 py-4"><span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{roleNames[employee.role] || employee.role}</span></td><td className="px-4 py-4 text-slate-600">{employee.email}</td><td className="px-4 py-4 text-slate-600">{employee.phone || '—'}</td><td className="px-4 py-4"><span className={`inline-flex items-center gap-1.5 text-xs font-semibold ${employee.is_active ? 'text-emerald-700' : 'text-rose-700'}`}><span className={`h-1.5 w-1.5 rounded-full ${employee.is_active ? 'bg-emerald-500' : 'bg-rose-500'}`} />{employee.is_active ? 'Activa' : 'Desactivada'}</span></td><td className="px-6 py-4"><div className="flex justify-end gap-2"><button type="button" onClick={() => updateStatus(employee)} className={`rounded-lg px-3 py-2 text-xs font-bold ${employee.is_active ? 'bg-amber-50 text-amber-900 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-900 hover:bg-emerald-100'}`}>{employee.is_active ? 'Desactivar' : 'Reactivar'}</button><button type="button" onClick={() => removeEmployee(employee)} disabled={!employee.can_delete} title={employee.can_delete ? 'Eliminar cuenta sin historial' : 'Tiene operaciones asociadas; desactívala para conservar la auditoría'} className="rounded-lg bg-rose-50 px-3 py-2 text-xs font-bold text-rose-800 hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-40">Eliminar</button></div></td></tr>) : <tr><td colSpan="6" className="px-6 py-14 text-center"><p className="font-bold text-slate-700">No hay empleados que coincidan.</p><p className="mt-1 text-sm text-slate-500">Ajusta los filtros o registra el primer empleado.</p></td></tr>}
                            </tbody>
                        </table>
                    </div>
                    <div className="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 px-5 py-4 sm:px-6"><p className="text-xs text-slate-500">Mostrando {employees.from ?? 0}–{employees.to ?? 0} de {employees.total}</p><Pagination links={employees.links} /></div>
                </section>
                <div className="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white/70 px-4 py-3 text-xs text-slate-600"><span>El alta de personal solo está disponible para cuentas administradoras.</span><Link href="/almacen" className="font-bold text-teal-800 hover:underline">Ir al control de inventario →</Link></div>
            </main>
            {dialogOpen && <EmployeeDialog roles={roleOptions} onClose={() => setDialogOpen(false)} />}
        </OperationsLayout>
    );
}
