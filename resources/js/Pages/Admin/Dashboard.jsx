import React from 'react';
import { Link } from '@inertiajs/react';
import OperationsLayout from '../../Layouts/OperationsLayout';

const roleLabels = {
    administrador: 'Administradores',
    almacenista: 'Almacenistas',
    cajero_pos: 'Cajeros POS',
    veterinario: 'Veterinarios',
};

const modules = [
    { href: '/admin/empleados', icon: '◎', title: 'Gestión de empleados', description: 'Consulta el directorio del personal y registra cuentas para cada rol.' },
    { href: '/almacen', icon: '▤', title: 'Inventario y almacén', description: 'Revisa existencias, lotes, movimientos, recepciones y salidas.' },
    { href: '/almacen/picking', icon: '✓', title: 'Preparación de pedidos', description: 'Supervisa el picking, marca pedidos listos y registra despachos.' },
    { href: '/pos', icon: '▦', title: 'Punto de venta', description: 'Registra ventas simuladas y consulta el movimiento reciente de caja.' },
    { href: '/pos/recolecciones', icon: '⌑', title: 'Entregas y recolecciones', description: 'Valida el código de recogida y registra la entrega del pedido.' },
    { href: '/pos/cierre-z', icon: '▣', title: 'Cierre de caja', description: 'Consulta o registra el corte diario de la caja.' },
    { href: '/admin/pruebas', icon: '◷', title: 'Pruebas y reportes', description: 'Consulta reportes locales de PHPUnit, Selenium, JMeter y PostgreSQL.' },
    { href: '/catalogo', icon: '◈', title: 'Catálogo público', description: 'Revisa el catálogo como lo ven los clientes.' },
];

function Metric({ label, value, note, tone = 'light' }) {
    const styles = {
        light: 'bg-white text-[#0b192c]',
        dark: 'bg-[#0b192c] text-white',
        teal: 'bg-[#e1f8f8] text-[#0b192c]',
        amber: 'bg-amber-50 text-amber-950',
    };

    return <article className={`rounded-2xl p-5 shadow-sm ring-1 ring-slate-900/[0.05] ${styles[tone]}`}>
        <p className="text-xs font-bold uppercase tracking-wider opacity-70">{label}</p>
        <p className="mt-3 text-3xl font-black tracking-tight">{value}</p>
        <p className="mt-1 text-xs opacity-70">{note}</p>
    </article>;
}

export default function AdminDashboard({ auth, summary, roleCounts }) {
    return <OperationsLayout title="Panel Administrador" auth={auth} eyebrow="ADMINISTRACIÓN · CENTRO DE OPERACIONES">
        <main className="mx-auto max-w-[1480px] px-4 py-7 sm:px-7 sm:py-10">
            <section className="flex flex-wrap items-end justify-between gap-5">
                <div>
                    <p className="text-xs font-extrabold uppercase tracking-[0.15em] text-teal-700">Panel de control</p>
                    <h1 className="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Hola, {auth.user?.name}</h1>
                    <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Administra el personal y accede a las áreas operativas de AQUARIUM desde un solo lugar. Las operaciones de cobro y pago en este entorno son simuladas.</p>
                </div>
                <Link href="/admin/empleados" className="inline-flex items-center gap-2 rounded-xl bg-[#08c2cd] px-5 py-3 text-sm font-extrabold text-[#0b192c] shadow-sm transition hover:bg-[#20d5dc]"><span className="text-lg leading-none">+</span> Registrar empleado</Link>
            </section>

            <section className="mt-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen operativo">
                <Metric label="Empleados" value={summary.employees} note="Cuentas de todos los roles" tone="dark" />
                <Metric label="Productos activos" value={summary.products} note="En el catálogo e inventario" tone="teal" />
                <Metric label="Stock bajo" value={summary.lowStock} note="Entre 1 y 5 piezas" tone="amber" />
                <Metric label="Agotados" value={summary.outOfStock} note="Requieren reposición" />
            </section>

            <section className="mt-8 grid gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
                <div>
                    <div className="mb-4"><h2 className="text-xl font-extrabold">Funciones administrativas</h2><p className="mt-1 text-sm text-slate-600">Abre el módulo que necesitas para operar o supervisar la sucursal.</p></div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {modules.map((module) => <Link key={module.href} href={module.href} className="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-300 hover:shadow-md">
                            <div className="flex items-start gap-4"><span className="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#e5f8f8] text-lg font-black text-teal-800">{module.icon}</span><div><h3 className="font-extrabold text-[#0b192c] group-hover:text-teal-800">{module.title}</h3><p className="mt-1 text-sm leading-5 text-slate-600">{module.description}</p></div></div>
                            <p className="mt-4 text-xs font-extrabold text-teal-800">Abrir módulo <span aria-hidden="true">→</span></p>
                        </Link>)}
                    </div>
                </div>

                <aside className="h-fit rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-900/[0.05]">
                    <p className="text-xs font-extrabold uppercase tracking-wider text-teal-700">Equipo por rol</p>
                    <h2 className="mt-1 text-lg font-extrabold">Personal registrado</h2>
                    <ul className="mt-4 divide-y divide-slate-100">
                        {Object.entries(roleLabels).map(([role, label]) => <li key={role} className="flex items-center justify-between gap-3 py-3 text-sm"><span className="text-slate-600">{label}</span><span className="font-extrabold tabular-nums">{roleCounts[role] ?? 0}</span></li>)}
                    </ul>
                    <Link href="/admin/empleados" className="mt-3 inline-flex w-full justify-center rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-[#0b192c] transition hover:border-teal-300 hover:bg-teal-50">Ver directorio completo</Link>
                </aside>
            </section>
        </main>
    </OperationsLayout>;
}
