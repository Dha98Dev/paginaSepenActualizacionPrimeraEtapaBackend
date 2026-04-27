<?php

namespace App\Http\Controllers\Api\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Permiso;
use App\Models\Persona;
use App\Models\Usuario;
use App\Models\UsuarioPermiso;
use App\Models\UsuarioRol;
use App\Models\UsuarioSesione;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class usuarioController extends Controller
{
    public function storeUsuario(Request $request)
    {
        $this->normalizarPayload($request);

        $validator = Validator::make($request->all(), [
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:80',
                'regex:/^[\pL\s\'.-]+$/u',
            ],
            'apellido_paterno' => [
                'required',
                'string',
                'min:2',
                'max:60',
                'regex:/^[\pL\s\'.-]+$/u',
            ],
            'apellido_materno' => [
                'required',
                'string',
                'min:2',
                'max:60',
                'regex:/^[\pL\s\'.-]+$/u',
            ],
            'curp' => [
                'required',
                'string',
                'size:18',
                'regex:/^[A-Z][AEIOUX][A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HM](AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d]\d$/',
            ],
            'rfc' => [
                'required',
                'string',
                'min:12',
                'max:13',
                'regex:/^([A-ZÑ&]{3,4})\d{6}[A-Z0-9]{3}$/',
            ],
            'correo' => [
                'required',
                'email',
                'max:120',
            ],
            'puesto' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[\pL\d\s.,()\-#\/]+$/u',
            ],
            'area' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[\pL\d\s.,()\-#\/]+$/u',
            ],
            'estatus' => [
                'required',
                Rule::in(['ACTIVO', 'INACTIVO']),
            ],

            'username' => [
                'required',
                'string',
                'min:4',
                'max:30',
                'regex:/^[A-Za-z0-9._-]+$/',
                'unique:usuarios,username',
            ],
            'email' => [
                'required',
                'email',
                'max:120',
                'unique:usuarios,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:64',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,64}$/',
            ],
            'confirmPassword' => [
                'required',
                'same:password',
            ],
            'activo' => [
                'required',
                'boolean',
            ],
            'debe_cambiar_password' => [
                'required',
                'boolean',
            ],

            'rolesSeleccionados' => [
                'required',
                'array',
                'min:1',
            ],
            'rolesSeleccionados.*' => [
                'integer',
                'distinct',
                'exists:roles,id',
            ],

            'permisosSeleccionados' => [
                'required',
                'array',
                'min:1',
            ],
            'permisosSeleccionados.*' => [
                'integer',
                'distinct',
                'exists:permisos,id',
            ],
        ], [
            'username.unique' => 'Intente con otro nombre de usuario ',
            'email.unique' => 'Intente con otro  correo de acceso.',
            'confirmPassword.same' => 'La confirmación de contraseña no coincide.',
            'rolesSeleccionados.min' => 'Debes seleccionar al menos un rol.',
            'permisosSeleccionados.min' => 'Debes seleccionar al menos un permiso.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $rolesSeleccionados = array_values(array_unique($data['rolesSeleccionados']));
        $permisosSeleccionados = array_values(array_unique($data['permisosSeleccionados']));

        $permisosPermitidosPorRoles = Permiso::whereHas('rol_permisos', function ($query) use ($rolesSeleccionados) {
            $query->whereIn('rol_id', $rolesSeleccionados);
        })->pluck('id')->toArray();

        $permisosInvalidos = array_diff($permisosSeleccionados, $permisosPermitidosPorRoles);

        if (!empty($permisosInvalidos)) {
            return response()->json([
                'ok' => false,
                'message' => 'Se enviaron permisos que no pertenecen a los roles seleccionados.',
                'errors' => [
                    'permisosSeleccionados' => ['Hay permisos que no pertenecen a los roles seleccionados.'],
                ],
            ], 422);
        }

        DB::beginTransaction();

        try {
            $personaPorCurp = Persona::where('curp', $data['curp'])->first();
            $personaPorRfc = Persona::where('rfc', $data['rfc'])->first();

            if ($personaPorCurp && $personaPorRfc && $personaPorCurp->id !== $personaPorRfc->id) {
                DB::rollBack();

                return response()->json([
                    'ok' => false,
                    'message' => 'Inconsistencia en los datos de la persona.',
                    'errors' => [
                        'curp' => ['La CURP pertenece a una persona distinta al RFC capturado.'],
                        'rfc' => ['El RFC pertenece a una persona distinta a la CURP capturada.'],
                    ],
                ], 422);
            }

            $persona = $personaPorCurp ?: $personaPorRfc;

            if (!$persona) {
                $persona = Persona::create([
                    'nombre' => $data['nombre'],
                    'apellido_paterno' => $data['apellido_paterno'],
                    'apellido_materno' => $data['apellido_materno'],
                    'curp' => $data['curp'],
                    'rfc' => $data['rfc'],
                    'correo' => $data['correo'],
                    'puesto' => $data['puesto'],
                    'area' => $data['area'],
                    'estatus' => $data['estatus'],
                ]);
            } else {
                $persona->update([
                    'nombre' => $data['nombre'],
                    'apellido_paterno' => $data['apellido_paterno'],
                    'apellido_materno' => $data['apellido_materno'],
                    'curp' => $data['curp'],
                    'rfc' => $data['rfc'],
                    'correo' => $data['correo'],
                    'puesto' => $data['puesto'],
                    'area' => $data['area'],
                    'estatus' => $data['estatus'],
                ]);
            }

            $usuario = Usuario::create([
                'persona_id' => $persona->id,
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'activo' => (bool) $data['activo'],
                'debe_cambiar_password' => (bool) $data['debe_cambiar_password'],
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
                'ultimo_acceso_at' => null,
                'ultimo_cambio_password_at' => now(),
                'remember_token' => null,
            ]);

            foreach ($rolesSeleccionados as $rolId) {
                UsuarioRol::create([
                    'usuario_id' => $usuario->id,
                    'rol_id' => $rolId,
                ]);
            }

            foreach ($permisosSeleccionados as $permisoId) {
                UsuarioPermiso::create([
                    'usuario_id' => $usuario->id,
                    'permiso_id' => $permisoId,
                    'permitido' => true,
                ]);
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Usuario registrado correctamente.',
                'data' => [
                    'usuario_id' => $usuario->id,
                    'persona_id' => $persona->id,
                    'username' => $usuario->username,
                    'email' => $usuario->email,
                    'activo' => $usuario->activo,
                    'debe_cambiar_password' => $usuario->debe_cambiar_password,
                    'roles' => $rolesSeleccionados,
                    'permisos' => $permisosSeleccionados,
                    'persona_reutilizada' => ($personaPorCurp || $personaPorRfc) ? true : false,
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'message' => 'No fue posible registrar el usuario.',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }
    public function indexUsuarios(Request $request)
    {
        $usuarios = Usuario::with([
            'persona:id,nombre,apellido_paterno,apellido_materno,correo,puesto,area,estatus',
            'roles:id,nombre'
        ])
            ->select([
                'id',
                'persona_id',
                'username',
                'email',
                'activo',
                'ultimo_acceso_at',
                'created_at'
            ])
            ->orderByDesc('id')
            ->get()
            ->map(function ($usuario) {
                $persona = $usuario->persona;

                return [
                    'id' => $usuario->id,
                    'persona_id' => $usuario->persona_id,
                    'nombre_completo' => trim(
                        ($persona->nombre ?? '') . ' ' .
                        ($persona->apellido_paterno ?? '') . ' ' .
                        ($persona->apellido_materno ?? '')
                    ),
                    'username' => $usuario->username,
                    'email' => $usuario->email,
                    'correo_persona' => $persona->correo ?? null,
                    'puesto' => $persona->puesto ?? null,
                    'area' => $persona->area ?? null,
                    'estatus_persona' => $persona->estatus ?? null,
                    'activo' => (bool) $usuario->activo,
                    'ultimo_acceso_at' => $usuario->ultimo_acceso_at,
                    'created_at' => $usuario->created_at,
                    'roles' => $usuario->roles->map(function ($rol) {
                        return [
                            'id' => $rol->id,
                            'nombre' => $rol->nombre,
                        ];
                    })->values(),
                ];
            });

        return response()->json([
            'ok' => true,
            'message' => 'Listado de usuarios obtenido correctamente.',
            'data' => $usuarios,
        ], 200);
    }
    public function deleteUsuario($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json([
                'ok' => false,
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        //  Evitar que el usuario se desactive a sí mismo
        if (auth()->id() === $usuario->id) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes desactivar tu propio usuario.'
            ], 403);
        }

        if (!$usuario->activo) {
            return response()->json([
                'ok' => false,
                'message' => 'El usuario ya se encuentra inactivo.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            //  Soft delete
            $usuario->activo = false;
            $usuario->intentos_fallidos = 0;
            $usuario->bloqueado_hasta = null;
            $usuario->save();

            //  Eliminar tokens activos (Sanctum)
            if (method_exists($usuario, 'tokens')) {
                $usuario->tokens()->delete();
            }

            //  Cerrar sesiones activas
            UsuarioSesione::where('usuario_id', $usuario->id)
                ->whereNull('cerrada_at')
                ->update([
                    'cerrada_at' => now(),
                    'es_actual' => false,
                    'token' => null,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Usuario desactivado y sesiones cerradas correctamente.',
                'data' => [
                    'id' => $usuario->id,
                    'activo' => $usuario->activo
                ]
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'message' => 'No fue posible desactivar el usuario.',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    public function updateUsuario(Request $request, $id)
    {
        $usuario = Usuario::with('persona')->find($id);

        if (!$usuario) {
            return response()->json([
                'ok' => false,
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        //  evitar editarte a ti mismo (opcional)
        // if (auth()->id() === $usuario->id) { ... }

        $this->normalizarPayload($request);

        $validator = Validator::make($request->all(), [

            // PERSONA
            'nombre' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s\'.-]+$/u'],
            'apellido_paterno' => ['required', 'string', 'min:2', 'max:60', 'regex:/^[\pL\s\'.-]+$/u'],
            'apellido_materno' => ['required', 'string', 'min:2', 'max:60', 'regex:/^[\pL\s\'.-]+$/u'],

            'curp' => [
                'required',
                'string',
                'size:18',
                Rule::unique('personas', 'curp')->ignore($usuario->persona_id),
            ],

            'rfc' => [
                'required',
                'string',
                'min:12',
                'max:13',
                Rule::unique('personas', 'rfc')->ignore($usuario->persona_id),
            ],

            'correo' => [
                'required',
                'email',
                'max:120',
                Rule::unique('personas', 'correo')->ignore($usuario->persona_id),
            ],

            'puesto' => ['required', 'string', 'max:100'],
            'area' => ['required', 'string', 'max:100'],
            'estatus' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],

            // USUARIO
            'username' => [
                'required',
                'string',
                'min:4',
                'max:30',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('usuarios', 'username')->ignore($usuario->id),
            ],

            'email' => [
                'required',
                'email',
                'max:120',
                Rule::unique('usuarios', 'email')->ignore($usuario->id),
            ],

            // contraseña opcional
            'password' => [
                'nullable',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/'
            ],

            'confirmPassword' => [
                'nullable',
                'same:password'
            ],

            'activo' => ['required', 'boolean'],
            'debe_cambiar_password' => ['required', 'boolean'],

            'rolesSeleccionados' => ['required', 'array', 'min:1'],
            'rolesSeleccionados.*' => ['integer', 'exists:roles,id'],

            'permisosSeleccionados' => ['required', 'array', 'min:1'],
            'permisosSeleccionados.*' => ['integer', 'exists:permisos,id'],

        ], [
            'confirmPassword.same' => 'La confirmación de contraseña no coincide.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        DB::beginTransaction();

        try {

            // 🔹 ACTUALIZAR PERSONA
            $usuario->persona->update([
                'nombre' => $data['nombre'],
                'apellido_paterno' => $data['apellido_paterno'],
                'apellido_materno' => $data['apellido_materno'],
                'curp' => $data['curp'],
                'rfc' => $data['rfc'],
                'correo' => $data['correo'],
                'puesto' => $data['puesto'],
                'area' => $data['area'],
                'estatus' => $data['estatus'],
            ]);

            // 🔹 ACTUALIZAR USUARIO
            $usuario->update([
                'username' => $data['username'],
                'email' => $data['email'],
                'activo' => $data['activo'],
                'debe_cambiar_password' => $data['debe_cambiar_password'],
            ]);

            //  contraseña opcional
            if (!empty($data['password'])) {
                $usuario->password = Hash::make($data['password']);
                $usuario->ultimo_cambio_password_at = now();
                $usuario->save();
            }

            //  ROLES
            $usuario->roles()->sync($data['rolesSeleccionados']);

            //  PERMISOS (reset completo)
            UsuarioPermiso::where('usuario_id', $usuario->id)->delete();

            foreach ($data['permisosSeleccionados'] as $permisoId) {
                UsuarioPermiso::create([
                    'usuario_id' => $usuario->id,
                    'permiso_id' => $permisoId,
                    'permitido' => true
                ]);
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Usuario actualizado correctamente.',
                'data' => [
                    'id' => $usuario->id,
                    'persona_id' => $usuario->persona_id,
                    'username' => $usuario->username,
                    'email' => $usuario->email,
                    'activo' => $usuario->activo
                ]
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'message' => 'No fue posible actualizar el usuario.',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }
    public function showUsuario($id)
    {
        $usuario = Usuario::with([
            'persona',
            'roles:id,nombre',
            'usuario_permisos.permiso:id,nombre'
        ])->find($id);

        if (!$usuario) {
            return response()->json([
                'ok' => false,
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        $persona = $usuario->persona;

        $rolesSeleccionados = $usuario->roles
            ->pluck('id')
            ->values();

        $permisosSeleccionados = $usuario->usuario_permisos
            ->where('permitido', true)
            ->pluck('permiso_id')
            ->unique()
            ->values();

        return response()->json([
            'ok' => true,
            'message' => 'Detalle del usuario obtenido correctamente.',
            'data' => [
                'id' => $usuario->id,
                'persona_id' => $usuario->persona_id,

                // datos personales
                'nombre' => $persona?->nombre,
                'apellido_paterno' => $persona?->apellido_paterno,
                'apellido_materno' => $persona?->apellido_materno,
                'curp' => $persona?->curp,
                'rfc' => $persona?->rfc,
                'correo' => $persona?->correo,
                'puesto' => $persona?->puesto,
                'area' => $persona?->area,
                'estatus' => $persona?->estatus,

                // datos de acceso
                'username' => $usuario->username,
                'email' => $usuario->email,
                'activo' => (bool) $usuario->activo,
                'debe_cambiar_password' => (bool) $usuario->debe_cambiar_password,

                // para autocompletar checks/selects
                'rolesSeleccionados' => $rolesSeleccionados,
                'permisosSeleccionados' => $permisosSeleccionados,

                // opcional: detalle visual
                'roles' => $usuario->roles->map(function ($rol) {
                    return [
                        'id' => $rol->id,
                        'nombre' => $rol->nombre,
                    ];
                })->values(),

                'permisos' => $usuario->usuario_permisos
                    ->where('permitido', true)
                    ->map(function ($usuarioPermiso) {
                        return [
                            'id' => $usuarioPermiso->permiso_id,
                            'nombre' => optional($usuarioPermiso->permiso)->nombre,
                        ];
                    })
                    ->unique('id')
                    ->values(),
            ]
        ], 200);
    }

    private function normalizarPayload(Request $request): void
    {
        $request->merge([
            'nombre' => $this->upperTrim($request->input('nombre')),
            'apellido_paterno' => $this->upperTrim($request->input('apellido_paterno')),
            'apellido_materno' => $this->upperTrim($request->input('apellido_materno')),
            'curp' => $this->upperTrim($request->input('curp')),
            'rfc' => $this->upperTrim($request->input('rfc')),
            'puesto' => $this->upperTrim($request->input('puesto')),
            'area' => $this->upperTrim($request->input('area')),
            'estatus' => $this->upperTrim($request->input('estatus')),
        ]);
    }

    private function upperTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strtoupper(trim($value), 'UTF-8');
    }
}
