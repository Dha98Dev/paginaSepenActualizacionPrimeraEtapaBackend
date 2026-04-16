<?php


namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'usuario' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $usuarioInput = trim($request->usuario);

        $user = Usuario::with(['persona', 'roles'])
            ->where('username', $usuarioInput)
            ->orWhere('email', $usuarioInput)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'usuario' => ['Las credenciales son incorrectas.'],
            ]);
        }

        if (!$user->activo) {
            return response()->json([
                'ok' => false,
                'message' => 'El usuario está inactivo.',
            ], 403);
        }

        if ($user->bloqueado_hasta && now()->lt($user->bloqueado_hasta)) {
            return response()->json([
                'ok' => false,
                'message' => 'El usuario está bloqueado temporalmente.',
                'bloqueado_hasta' => $user->bloqueado_hasta,
            ], 423);
        }

        if (!Hash::check($request->password, $user->password)) {
            $intentos = $user->intentos_fallidos + 1;

            $data = [
                'intentos_fallidos' => $intentos,
            ];

            if ($intentos >= 5) {
                $data['bloqueado_hasta'] = now()->addMinutes(15);
            }

            $user->update($data);

            throw ValidationException::withMessages([
                'usuario' => ['Las credenciales son incorrectas.'],
            ]);
        }

        $user->update([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'ultimo_acceso_at' => now(),
        ]);

        $permisos = $user->roles
            ->flatMap(function ($rol) {
                return $rol->rol_permisos ?? collect();
            });

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'ok' => true,
            'message' => 'Inicio de sesión exitoso.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'usuario' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'activo' => $user->activo,
                    'debe_cambiar_password' => $user->debe_cambiar_password,
                    'persona' => $user->persona ? [
                        'id' => $user->persona->id,
                        'nombre' => $user->persona->nombre,
                        'apellido_paterno' => $user->persona->apellido_paterno,
                        'apellido_materno' => $user->persona->apellido_materno,
                        'correo' => $user->persona->correo,
                    ] : null,
                    'roles' => $user->roles->map(function ($rol) {
                        return [
                            'id' => $rol->id,
                            'nombre' => $rol->nombre,
                            'slug' => $rol->slug,
                        ];
                    })->values(),
                ],
            ],
        ]);
    }
    public function me(Request $request)
    {
        $authUser = $request->user();

        if (!$authUser) {
            return response()->json([
                'ok' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        $user = Usuario::with([
            'persona',
            'roles.permisos',
            'usuario_permisos.permiso'
        ])->find($authUser->id);

        // 🔹 1. Permisos base (roles)
        $permisosRol = $user->roles
            ->flatMap(function ($rol) {
                return $rol->permisos;
            })
            ->pluck('slug')
            ->unique();

        // 🔹 2. Permisos del usuario (overrides)
        $permisosUsuario = $user->usuario_permisos;

        // ➕ permisos permitidos explícitamente
        $permitidos = $permisosUsuario
            ->where('permitido', true)
            ->pluck('permiso.slug');

        // ➖ permisos negados explícitamente
        $negados = $permisosUsuario
            ->where('permitido', false)
            ->pluck('permiso.slug');

        // 🔹 3. Construir permisos finales
        $permisosFinales = $permisosRol
            ->merge($permitidos)   // agregar extras
            ->diff($negados)       // quitar negados
            ->unique()
            ->values();

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'activo' => $user->activo,
                'debe_cambiar_password' => $user->debe_cambiar_password,

                'persona' => $user->persona ? [
                    'id' => $user->persona->id,
                    'nombre' => $user->persona->nombre,
                    'apellido_paterno' => $user->persona->apellido_paterno,
                    'apellido_materno' => $user->persona->apellido_materno,
                    'correo' => $user->persona->correo,
                ] : null,

                'roles' => $user->roles->map(function ($rol) {
                    return [
                        'id' => $rol->id,
                        'nombre' => $rol->nombre,
                        'slug' => $rol->slug,
                    ];
                })->values(),

                'permisos' => $permisosFinales
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
