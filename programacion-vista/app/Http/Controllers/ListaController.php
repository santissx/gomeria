<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class listacontroller extends Controller
{
    public function mostrar(Request $request)
    {
        // Capturamos el texto de búsqueda
        $search = $request->input('search');
        $sort = $request->input('sort');

        // =========================================================
        // NUEVO: CAPTURAMOS LA BÚSQUEDA POR PROVEEDOR Y CATEGORÍA
        // =========================================================

        $proveedor = $request->input('proveedor');
        $categoria = $request->input('categoria');


        // =========================================================
        // 1. INICIAMOS LA CONSULTA SOBRE PRODUCTOS
        // =========================================================

        $query = DB::table('productos');


        // =========================================================
        // NUEVO: BUSCAR POR NOMBRE DE PROVEEDOR
        // =========================================================

        if (!empty($proveedor)) {

            // Normalizamos la búsqueda
            $proveedorLimpio = trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    mb_strtolower($proveedor, 'UTF-8')
                )
            );

            // Quitamos tildes
            $proveedorSinTilde = str_replace(
                ['á', 'é', 'í', 'ó', 'ú', '´'],
                ['a', 'e', 'i', 'o', 'u', ''],
                $proveedorLimpio
            );

            // Buscamos los proveedores que coincidan con el nombre
            $proveedoresEncontrados = DB::table('proveedores')
                ->where(function ($q) use ($proveedorSinTilde) {

                    $columnaProveedorLimpia =
                        "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(proveedores.nombre, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), '´', ''))";

                    $q->where(
                        DB::raw($columnaProveedorLimpia),
                        'LIKE',
                        '%' . $proveedorSinTilde . '%'
                    );

                })
                ->pluck('id_proveedor');


            // IMPORTANTE:
            // Mostramos solamente los productos pertenecientes
            // a los proveedores encontrados.
            $query->whereIn(
                'productos.id_proveedor',
                $proveedoresEncontrados
            );
        }


        // =========================================================
        // NUEVO: BUSCAR POR NOMBRE DE CATEGORÍA
        // =========================================================

        if (!empty($categoria)) {

            // Normalizamos la búsqueda
            $categoriaLimpia = trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    mb_strtolower($categoria, 'UTF-8')
                )
            );

            // Quitamos tildes
            $categoriaSinTilde = str_replace(
                ['á', 'é', 'í', 'ó', 'ú', '´'],
                ['a', 'e', 'i', 'o', 'u', ''],
                $categoriaLimpia
            );

            // Buscamos las categorías que coincidan con el nombre
            $categoriasEncontradas = DB::table('categorias')
                ->where(function ($q) use ($categoriaSinTilde) {

                    $columnaCategoriaLimpia =
                        "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(categorias.categoria, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), '´', ''))";

                    $q->where(
                        DB::raw($columnaCategoriaLimpia),
                        'LIKE',
                        '%' . $categoriaSinTilde . '%'
                    );

                })
                ->pluck('id_categoria');


            // IMPORTANTE:
            // Mostramos solamente los productos pertenecientes
            // a las categorías encontradas.
            $query->whereIn(
                'productos.id_categoria',
                $categoriasEncontradas
            );
        }


        // =========================================================
        // 2. BÚSQUEDA ORIGINAL DE PRODUCTOS
        // =========================================================

        if (!empty($search)) {

            // Normalizamos el string de la URL
            $busquedaLimpia = trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    mb_strtolower($search, 'UTF-8')
                )
            );

            // Separamos por palabras individuales
            $palabras = array_filter(
                explode(' ', $busquedaLimpia)
            );


            $query->where(function ($q) use ($palabras) {

                foreach ($palabras as $palabra) {

                    // Quitamos las tildes y el acento suelto ´
                    $palabraSinTilde = str_replace(
                        ['á', 'é', 'í', 'ó', 'ú', '´'],
                        ['a', 'e', 'i', 'o', 'u', ''],
                        $palabra
                    );


                    // Reemplazamos virtualmente las tildes
                    // en la columna nombre de productos
                    $columnaNombreLimpia =
                        "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(productos.nombre, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), '´', ''))";


                    $q->where(function ($subQ) use (
                        $columnaNombreLimpia,
                        $palabraSinTilde,
                        $palabra
                    ) {

                        $subQ->where(
                            DB::raw($columnaNombreLimpia),
                            'LIKE',
                            '%' . $palabraSinTilde . '%'
                        )
                        ->orWhere(
                            'productos.codigo',
                            'LIKE',
                            '%' . $palabra . '%'
                        )
                        ->orWhere(
                            'productos.codigo_barra',
                            'LIKE',
                            '%' . $palabra . '%'
                        );

                    });

                }

            });

        }


        // =========================================================
        // 3. FILTRO POR ID DE PROVEEDOR
        // =========================================================
        // Se mantiene el filtro que ya tenías en el modal.

        if ($request->filled('id_proveedor')) {

            $query->where(
                'productos.id_proveedor',
                $request->input('id_proveedor')
            );

        }


        // =========================================================
        // 4. FILTRO POR ID DE CATEGORÍA
        // =========================================================
        // Se mantiene el filtro que ya tenías en el modal.

        if ($request->filled('id_categoria')) {

            $query->where(
                'productos.id_categoria',
                $request->input('id_categoria')
            );

        }


        // =========================================================
        // 5. ORDENAR POR STOCK
        // =========================================================

        if ($sort === 'stock_asc') {

            $query->orderBy(
                'productos.stock',
                'asc'
            );

        } elseif ($sort === 'stock_desc') {

            $query->orderBy(
                'productos.stock',
                'desc'
            );

        }


        // =========================================================
        // 6. OBTENEMOS LOS PRODUCTOS
        // =========================================================

        $query->leftJoin('estanterias', 'productos.id_estanteria', '=', 'estanterias.id_estanteria')
            ->select(
                'productos.*',
                'estanterias.identificador as estanteria_identificador',
                'estanterias.pasillo as estanteria_pasillo'
            );

        $productosFiltrados = $query->get();


        // =========================================================
        // 7. ADJUNTAMOS CATEGORÍA Y PROVEEDOR
        // =========================================================

        $productosMapeados = [];

        foreach ($productosFiltrados as $producto) {

            $categoriaNombre = DB::table('categorias')
                ->where(
                    'id_categoria',
                    $producto->id_categoria
                )
                ->value('categoria');

            $proveedorNombre = DB::table('proveedores')
                ->where(
                    'id_proveedor',
                    $producto->id_proveedor
                )
                ->value('nombre');

            $producto->categoria =
                $categoriaNombre ?? 'Sin categoría';

            $producto->nombre_proveedor =
                $proveedorNombre ?? 'Sin proveedor';

            $productosMapeados[] = $producto;
        }

        $productos = collect($productosMapeados);


        // =========================================================
        // 8. CARGAMOS PROVEEDORES Y CATEGORÍAS
        // =========================================================

        $proveedores = DB::table('proveedores')
            ->where('estado', 'activo')
            ->get();


        $categorias = DB::table('categorias')
            ->get();

        $estanterias = DB::table('estanterias')
            ->where('estado', 'activo')
            ->orderBy('pasillo')
            ->orderBy('identificador')
            ->get();

        // =========================================================
        // 9. ENVIAMOS TODO A LA VISTA
        // =========================================================

        return view('lista', [
            'productos'   => $productos,
            'proveedores' => $proveedores,
            'categorias'  => $categorias,
            'estanterias' => $estanterias,
            'search'      => $search,
            'sort'        => $sort,
            'proveedor'   => $proveedor,
            'categoria'   => $categoria,
        ]);
    }


    public function agregar(Request $request)
    {
        $request->merge([
            'precio_lista' => str_replace(',', '.', (string) $request->input('precio_lista')),
            'precio_venta' => str_replace(',', '.', (string) $request->input('precio_venta')),
        ]);

        $reglasCategoria = $request->input('id_categoria') === 'nueva'
            ? 'required'
            : 'required|integer|exists:categorias,id_categoria';

        $validated = $request->validate([
            'nombre'                => 'required|string|max:255',
            'codigo'                => 'required|string|max:255|unique:productos,codigo',
            'codigo_barra'          => 'required|string|max:255|unique:productos,codigo_barra',
            'precio_lista'          => 'required|numeric|min:0|regex:/^\d{1,10}(\.\d{1,2})?$/',
            'precio_venta'          => 'required|numeric|min:0|regex:/^\d{1,10}(\.\d{1,2})?$/',
            'id_proveedor'          => 'required|integer|exists:proveedores,id_proveedor',
            'id_categoria'          => $reglasCategoria,
            'nueva_categoria'       => 'nullable|string|max:255',
            'descripcion_categoria' => 'nullable|string|max:255',
            'stock'                 => 'required|integer|min:0',
            'id_estanteria'         => 'nullable|integer|exists:estanterias,id_estanteria',
            'posicion_estanteria'   => 'nullable|integer|min:1',
        ], [
            'codigo.unique'       => '¡Atención! El Código ingresado ya está asignado a otro producto existente.',
            'codigo_barra.unique' => '¡Atención! El Código de Barra ingresado ya pertenece a un artículo del inventario.',
        ]);

        $ubicacion = $this->resolverUbicacion($request);

        DB::transaction(function () use ($request, $validated, $ubicacion) {
            $idCategoria = $validated['id_categoria'];

            if ($idCategoria === 'nueva') {
                if (!$request->filled('nueva_categoria')) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'nueva_categoria' => 'Debe ingresar el nombre de la nueva categoría.',
                    ]);
                }

                if (!$request->filled('descripcion_categoria')) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'descripcion_categoria' => 'Debe ingresar la descripción de la nueva categoría.',
                    ]);
                }

                $nombreCategoria = trim($request->input('nueva_categoria'));
                $descripcionCategoria = trim($request->input('descripcion_categoria'));

                $categoriaExistente = DB::table('categorias')
                    ->where('categoria', $nombreCategoria)
                    ->first();

                if ($categoriaExistente) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'nueva_categoria' => 'La categoría "' . $nombreCategoria . '" ya existe.',
                    ]);
                }

                $idCategoria = DB::table('categorias')->insertGetId([
                    'categoria'   => $nombreCategoria,
                    'descripcion' => $descripcionCategoria,
                ]);
            }

            DB::table('productos')->insert([
                'nombre'       => $validated['nombre'],
                'codigo'       => $validated['codigo'],
                'codigo_barra' => $validated['codigo_barra'],
                'precio_lista' => $validated['precio_lista'],
                'precio_venta' => $validated['precio_venta'],
                'id_proveedor' => $validated['id_proveedor'],
                'id_categoria' => $idCategoria,
                'stock'        => $validated['stock'],
                'estado'       => 'activo',
                ...$ubicacion,
            ]);
        });

        return redirect()
            ->route('views.lista')
            ->with('success', 'Producto agregado correctamente.');
    }


    public function modificar(Request $request)
    {
        $request->merge([
            'precio_lista' => str_replace(',', '.', (string) $request->input('precio_lista')),
            'precio_venta' => str_replace(',', '.', (string) $request->input('precio_venta')),
        ]);

        $idProducto = $request->input('id_producto');

        $validated = $request->validate([
            'id_producto'         => 'required|integer|exists:productos,id_producto',
            'nombre'              => 'required|string|max:255',
            'codigo'              => 'required|string|max:255|unique:productos,codigo,' . $idProducto . ',id_producto',
            'codigo_barra'        => 'required|string|max:255|unique:productos,codigo_barra,' . $idProducto . ',id_producto',
            'precio_lista'        => 'required|numeric|min:0|regex:/^\d{1,10}(\.\d{1,2})?$/',
            'precio_venta'        => 'required|numeric|min:0|regex:/^\d{1,10}(\.\d{1,2})?$/',
            'id_proveedor'        => 'required|integer|exists:proveedores,id_proveedor',
            'id_categoria'        => 'required|integer|exists:categorias,id_categoria',
            'stock'               => 'required|integer|min:0',
            'estado'              => 'nullable|in:activo,desactivado',
            'id_estanteria'       => 'nullable|integer|exists:estanterias,id_estanteria',
            'posicion_estanteria' => 'nullable|integer|min:1',
        ], [
            'codigo.unique'       => '¡Atención! El Código ingresado ya está asignado a otro producto existente.',
            'codigo_barra.unique' => '¡Atención! El Código de Barra ya pertenece a un artículo del inventario.',
        ]);

        $ubicacion = $this->resolverUbicacion($request);

        DB::table('productos')
            ->where('id_producto', $validated['id_producto'])
            ->update([
                'nombre'       => $validated['nombre'],
                'codigo'       => $validated['codigo'],
                'codigo_barra' => $validated['codigo_barra'],
                'precio_lista' => $validated['precio_lista'],
                'precio_venta' => $validated['precio_venta'],
                'id_proveedor' => $validated['id_proveedor'],
                'id_categoria' => $validated['id_categoria'],
                'stock'        => $validated['stock'],
                'estado'       => $validated['estado'] ?? 'activo',
                ...$ubicacion,
            ]);

        return redirect()
            ->route('views.lista', [
                'search'       => $request->query('search'),
                'proveedor'    => $request->query('proveedor'),
                'categoria'    => $request->query('categoria'),
                'id_proveedor' => $request->query('id_proveedor'),
                'id_categoria' => $request->query('id_categoria'),
                'sort'         => $request->query('sort'),
            ])
            ->with('success', 'Producto modificado con éxito.');
    }


    public function borrar(
        Request $request,
        $id_producto
    )
    {
        DB::table('productos')
            ->where(
                'id_producto',
                $id_producto
            )
            ->update([
                'estado' => 'desactivado'
            ]);


        // =========================================================
        // NUEVO: CONSERVAR BÚSQUEDAS AL ELIMINAR
        // =========================================================

        return redirect()
            ->route('views.lista', [

                'search' =>
                    $request->query('search'),

                'proveedor' =>
                    $request->query('proveedor'),

                'categoria' =>
                    $request->query('categoria'),

                'sort' =>
                    $request->query('sort'),

            ])
            ->with(
                'success',
                'Producto desactivado con éxito.'
            );
    }


    public function aumentarPrecioPorProveedor(
        Request $request
    )
    {
        $validated = $request->validate([

            'id_proveedor' =>
                'required|exists:proveedores,id_proveedor',

            'porcentaje' =>
                'required|numeric|min:0',

        ]);


        $porcentaje =
            $validated['porcentaje'];


        DB::table('productos')
            ->where(
                'id_proveedor',
                $request->input('id_proveedor')
            )
            ->update([

                'precio_venta' =>
                    DB::raw(
                        'precio_venta * (1 + ' .
                        ($porcentaje / 100) .
                        ')'
                    ),

            ]);


        return redirect()
            ->back()
            ->with(
                'success',
                'Los precios se han actualizado correctamente.'
            );
    }


    public function aumentarPreciolistaPorProveedor(
        Request $request
    )
    {
        $validated = $request->validate([

            'id_proveedor' =>
                'required|exists:proveedores,id_proveedor',

            'porcentaje' =>
                'required|numeric|min:0',

        ]);


        $porcentaje =
            $validated['porcentaje'];


        DB::table('productos')
            ->where(
                'id_proveedor',
                $request->input('id_proveedor')
            )
            ->update([

                'precio_lista' =>
                    DB::raw(
                        'precio_lista * (1 + ' .
                        ($porcentaje / 100) .
                        ')'
                    ),

            ]);


        return redirect()
            ->back()
            ->with(
                'success',
                'Los precios se han actualizado correctamente.'
            );
    }

    private function resolverUbicacion(Request $request): array
    {
        $idEstanteria = $request->input('id_estanteria');
        $posicion = $request->input('posicion_estanteria');

        if (empty($idEstanteria)) {
            return [
                'id_estanteria'     => null,
                'posicion_estanteria' => null,
            ];
        }

        $estanteria = DB::table('estanterias')
            ->where('id_estanteria', $idEstanteria)
            ->first();

        if (!$estanteria) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'id_estanteria' => 'La estantería seleccionada no existe.',
            ]);
        }

        $posEntero = filter_var($posicion, FILTER_VALIDATE_INT);
        if ($posEntero === false || $posEntero < 1 || $posEntero > (int) $estanteria->posiciones) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'posicion_estanteria' => "La posición debe estar entre 1 y {$estanteria->posiciones}.",
            ]);
        }

        return [
            'id_estanteria'     => (int) $idEstanteria,
            'posicion_estanteria' => $posEntero,
        ];
    }
}

