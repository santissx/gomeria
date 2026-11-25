<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstanteriaController extends Controller
{
    public function index()
    {
        $estanterias = DB::table('estanterias')
            ->orderBy('pasillo')
            ->orderBy('identificador')
            ->get();

        return view('estanterias', [
            'estanterias' => $estanterias,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'identificador' => 'required|string|max:50',
            'pasillo'       => 'required|string|max:50',
            'posiciones'    => 'required|integer|min:1|max:500',
        ]);

        $identificador = trim($validated['identificador']);
        $pasillo = trim($validated['pasillo']);

        $existe = DB::table('estanterias')
            ->where('identificador', $identificador)
            ->where('pasillo', $pasillo)
            ->exists();

        if ($existe) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'identificador' => 'Ya existe una estantería con ese identificador en ese pasillo.',
                ]);
        }

        DB::table('estanterias')->insert([
            'identificador' => $identificador,
            'pasillo'       => $pasillo,
            'posiciones'    => $validated['posiciones'],
            'estado'        => 'activo',
        ]);

        return redirect()
            ->route('views.estanterias')
            ->with('success', 'Estantería dada de alta correctamente.');
    }
}