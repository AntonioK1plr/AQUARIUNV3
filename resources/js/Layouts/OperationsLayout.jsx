import React from 'react';
import { Head, router } from '@inertiajs/react';

function Mark() {
    return <span className="grid h-9 w-9 place-items-center rounded-full bg-[#08c2cd] text-sm font-black text-[#0b192c]">A</span>;
}

export default function OperationsLayout({ title, eyebrow = 'OPERACIÓN LOCAL', auth = { user: null }, children }) {
    const role = auth.user?.role;
    return (
        <div className="min-h-screen bg-[#f2f6f8] text-[#0b192c]">
            <Head title={`${title} · AQUARIUM`} />
            <header className="sticky top-0 z-40 border-b border-white/10 bg-[#0b192c] text-white shadow-lg shadow-slate-950/10">
                <div className="mx-auto flex min-h-[68px] max-w-[1480px] items-center justify-between gap-4 px-4 sm:px-7">
                    <a href="/" className="flex items-center gap-2.5"><Mark /><span className="font-black tracking-wide">AQUARIUM</span><span className="hidden rounded bg-[#08c2cd]/15 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-[#08c2cd] sm:inline">Operaciones</span></a>
                    <nav className="hidden items-center gap-5 text-sm md:flex" aria-label="Módulos internos">
                        {role === 'administrador' && <a href="/admin" className={`hover:text-[#08c2cd] ${title.includes('Panel') ? 'font-bold text-[#08c2cd]' : 'text-slate-300'}`}>Panel</a>}
                        {role === 'administrador' && <a href="/admin/empleados" className={`hover:text-[#08c2cd] ${title.includes('Personal') ? 'font-bold text-[#08c2cd]' : 'text-slate-300'}`}>Personal</a>}
                        {role === 'administrador' && <a href="/admin/pruebas" className={`hover:text-[#08c2cd] ${title.includes('Pruebas') ? 'font-bold text-[#08c2cd]' : 'text-slate-300'}`}>Pruebas</a>}
                        {(role === 'administrador' || role === 'almacenista') && <a href="/almacen" className={`hover:text-[#08c2cd] ${title.includes('Inventario') ? 'font-bold text-[#08c2cd]' : 'text-slate-300'}`}>Inventario</a>}
                        {(role === 'administrador' || role === 'almacenista') && <a href="/almacen/picking" className={`hover:text-[#08c2cd] ${title.includes('Preparación') ? 'font-bold text-[#08c2cd]' : 'text-slate-300'}`}>Picking</a>}
                        {role === 'veterinario' && <a href="/veterinaria/citas" className={`hover:text-[#08c2cd] ${title.includes('Veterinaria') ? 'font-bold text-[#08c2cd]' : 'text-slate-300'}`}>Agenda clínica</a>}
                        {(role === 'administrador' || role === 'cajero_pos') && <a href="/pos" className={`hover:text-[#08c2cd] ${title.includes('POS') ? 'font-bold text-[#08c2cd]' : 'text-slate-300'}`}>Terminal POS</a>}
                        <a href="/catalogo" className="text-slate-300 hover:text-[#08c2cd]">Catálogo público</a>
                    </nav>
                    <div className="flex items-center gap-3">
                        <div className="hidden text-right sm:block"><p className="text-xs font-bold text-white">{auth.user?.name}</p><p className="text-[10px] uppercase tracking-wider text-[#08c2cd]">{role?.replace('_', ' ')}</p></div>
                        <button type="button" onClick={() => router.post('/salir')} className="rounded-lg border border-white/15 px-3 py-2 text-xs font-bold text-slate-200 hover:border-[#08c2cd]/50 hover:text-white">Salir</button>
                    </div>
                </div>
                <div className="border-t border-white/10 bg-[#0d2036]">
                    <div className="mx-auto flex max-w-[1480px] items-center justify-between px-4 py-2 sm:px-7"><p className="text-[10px] font-extrabold tracking-[0.16em] text-[#08c2cd]">{eyebrow}</p><a href="/" className="text-[11px] font-semibold text-slate-400 hover:text-white">Vista pública ↗</a></div>
                </div>
            </header>
            {children}
            <footer className="border-t border-slate-200 bg-white px-5 py-4"><div className="mx-auto flex max-w-[1480px] justify-between gap-4 text-[11px] text-slate-500"><span>AQUARIUM · Operación local</span><span>Los cambios quedan registrados en la base local</span></div></footer>
        </div>
    );
}
