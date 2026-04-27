# 📘 Documentación API - Sistema de Gestión (Prensa / Escalafón)

## 🔐 Autenticación

### 🔹 Login
POST /auth/login

Body:
{
  "usuario": "string",
  "password": "string"
}

Response:
{
  "ok": true,
  "message": "Inicio de sesión exitoso",
  "data": {
    "token": "string",
    "token_type": "Bearer",
    "usuario": {
      "id": 1,
      "username": "admin",
      "email": "correo@correo.com"
    }
  }
}

---

### 🔹 Obtener usuario autenticado
GET /auth/me

Headers:
Authorization: Bearer {token}

Response:
{
  "ok": true,
  "data": {
    "id": 1,
    "username": "admin",
    "roles": [],
    "persona": {},
    "permisos": []
  }
}

---

## 📢 Convocatorias

GET /convocatorias
POST /convocatorias
POST /convocatorias/{id}
DELETE /convocatorias/{id}

---

## 📰 Boletines

GET /prensa/boletines
POST /prensa/boletines
PUT /prensa/boletines/{id}
DELETE /prensa/boletines/{id}

---

## 📎 Archivos

POST /prensa/boletines/archivos
DELETE /prensa/boletines/archivos/{archivoId}

---

## 📂 Documentos

POST /documentos
POST /documentos/{id}/actualizar
DELETE /documentos/{id}

GET /documentos/modulo/{modulo}
GET /documentos/boletines
GET /documentos/convocatorias-vacantes
GET /documentos/convocatorias-con-resultado
GET /documentos/por-tipo
GET /documentos/por-anio
GET /documentos/historico-catalogos-proyectos

---

## 📚 Catálogos

GET /prensa/catalogos/convocatorias-fechas
GET /escalafon/tipos-documentos/{modulo}
GET /escalafon/grupos-escalafon
GET /prensa/catalogos/modulos

---

## 🔐 Seguridad

Authorization: Bearer {token}

Permisos:
- Se validan por middleware
- Se combinan roles + overrides usuario

---

## 🚀 Flujo

1. Login
2. Guardar token
3. Llamar /auth/me
4. Guardar permisos
