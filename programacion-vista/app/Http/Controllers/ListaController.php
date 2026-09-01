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


        // Lo convertimos en colección
        $productos = collect($productosMapeados);


        // =========================================================
        // 8. CARGAMOS PROVEEDORES Y CATEGORÍAS
        // =========================================================

        $proveedores = DB::table('proveedores')
            ->where('estado', 'activo')
            ->get();


        $categorias = DB::table('categorias')
            ->get();


        // =========================================================
        // 9. ENVIAMOS TODO A LA VISTA
        // =========================================================

        return view('lista', [

            'productos'   => $productos,

            'proveedores' => $proveedores,

            'categorias'  => $categorias,

            'search'      => $search,

            'sort'        => $sort,

            // =====================================================
            // NUEVO: ENVIAMOS LAS BÚSQUEDAS A LA VISTA
            // =====================================================

            'proveedor'   => $proveedor,

            'categoria'   => $categoria,

        ]);
    }


    public function agregar(Request $request)
{
    $request->validate([
        'nombre' =>
            'required|string|max:255',

        'codigo' =>
            'required|string|max:255|unique:productos,codigo',

        'codigo_barra' =>
            'required|string|max:255|unique:productos,codigo_barra',

        'precio_lista' =>
            'required|numeric|regex:/^\d{1,10}(\.\d{0,2})?$/',

        'precio_venta' =>
            'required|numeric|regex:/^\d{1,10}(\.\d{0,2})?$/',

        'id_proveedor' =>
            'required|int|min:1',

        'id_categoria' =>
            'required',

        'nueva_categoria' =>
            'nullable|string|max:255',

        'descripcion_categoria' =>
            'nullable|string|max:255',

        'stock' =>
            'required|int',
    ], [
        'codigo.unique' =>
            '¡Atención! El Código ingresado ya está asignado a otro producto existente.',

        'codigo_barra.unique' =>
            '¡Atención! El Código de Barra ingresado ya pertenece a un artículo del inventario.',
    ]);

    DB::transaction(function () use ($request) {

        $idCategoria = $request->input('id_categoria');

        /*
        |--------------------------------------------------------------------------
        | NUEVA CATEGORÍA
        |--------------------------------------------------------------------------
        */

        if ($idCategoria === 'nueva') {

            // Validamos que haya ingresado el nombre
            if (!$request->filled('nueva_categoria')) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'nueva_categoria' =>
                        'Debe ingresar el nombre de la nueva categoría.'
                ]);
            }

            // Validamos que haya ingresado descripción
            if (!$request->filled('descripcion_categoria')) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'descripcion_categoria' =>
                        'Debe ingresar la descripción de la nueva categoría.'
                ]);
            }

            $nombreCategoria = trim(
                $request->input('nueva_categoria')
            );

            $descripcionCategoria = trim(
                $request->input('descripcion_categoria')
            );

            // Comprobar si ya existe
            $categoriaExistente = DB::table('categorias')
                ->where('categoria', $nombreCategoria)
                ->first();

            if ($categoriaExistente) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'nueva_categoria' =>
                        'La categoría "' . $nombreCategoria . '" ya existe.'
                ]);
            }

            // Crear categoría
            $idCategoria = DB::table('categorias')->insertGetId([
                'categoria' =>
                    $nombreCategoria,

                'descripcion' =>
                    $descripcionCategoria,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CREAR PRODUCTO
        |--------------------------------------------------------------------------
        */

        DB::table('productos')->insert([
            'nombre' =>
                $request->input('nombre'),

            'codigo' =>
                $request->input('codigo'),

            'codigo_barra' =>
                $request->input('codigo_barra'),

            'precio_lista' =>
                $request->input('precio_lista'),

            'precio_venta' =>
                $request->input('precio_venta'),

            'id_proveedor' =>
                $request->input('id_proveedor'),

            'id_categoria' =>
                $idCategoria,

            'stock' =>
                $request->input('stock'),

            'estado' =>
                'activo',
        ]);
    });

    return redirect()
        ->route('views.lista')
        ->with(
            'success',
            'Producto agregado correctamente.'
        );
}


    public function modificar(Request $request)
    {
        $id_producto =
            $request->input('id_producto');


        $request->validate([

            'id_producto' =>
                'required|exists:productos,id_producto|int',

            'nombre' =>
                'required|string|max:255',

            'codigo' =>
                'required|string|max:255|unique:productos,codigo,' .
                $id_producto .
                ',id_producto',

            'codigo_barra' =>
                'required|string|max:255|unique:productos,codigo_barra,' .
                $id_producto .
                ',id_producto',

            'precio_lista' =>
                'required|numeric|regex:/^\d{1,10}(\.\d{0,2})?$/',

            'precio_venta' =>
                'required|numeric|regex:/^\d{1,10}(\.\d{0,2})?$/',

            'id_proveedor' =>
                'required|int|min:1',

            'id_categoria' =>
                'required|int|min:1',

            'stock' =>
                'required|int',

            'estado' =>
                'nullable|string|max:255',

        ], [

            'codigo.unique' =>
                '¡Atención! El Código ingresado ya está asignado a otro producto existente.',

            'codigo_barra.unique' =>
                '¡Atención! El Código de Barra ya pertenece a un artículo del inventario.',

        ]);


        DB::table('productos')
            ->where(
                'id_producto',
                $id_producto
            )
            ->update([

                'nombre' =>
                    $request->input('nombre'),

                'codigo' =>
                    $request->input('codigo'),

                'codigo_barra' =>
                    $request->input('codigo_barra'),

                'precio_lista' =>
                    $request->input('precio_lista'),

                'precio_venta' =>
                    $request->input('precio_venta'),

                'id_proveedor' =>
                    $request->input('id_proveedor'),

                'id_categoria' =>
                    $request->input('id_categoria'),

                'stock' =>
                    $request->input('stock'),

                'estado' =>
                    $request->input('estado'),

            ]);


        // =========================================================
        // NUEVO: CONSERVAR BÚSQUEDAS AL MODIFICAR
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
                'Producto modified con éxito.'
            );
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
}

