# Comités Afectivo 2028 — Contexto del Proyecto

## ¿Qué es?
Sistema SaaS de gestión de comités afectivos para partidos políticos de República Dominicana.
Vendible a cualquier partido — cada uno tiene su propio tema, logo, candidatos y usuarios.

## Repositorio
https://github.com/gucci7up/comites2028.git

## Dominio activo
comitesprm.com

## Stack
- PHP 8.3 + Apache (Docker)
- MySQL — Dokploy managed (datos propios)
- SQL Server `148.0.129.233:1433` — padrón electoral (solo lectura)
- Bootstrap 5 + Inter font + Chart.js + ColorThief.js

---

## Jerarquía de roles

```
superadmin → partidos.php → crea partidos + owners
owner      → config.php   → logo, colores, candidatos
admin/usuario (digitador) → seleccionar_candidato.php → dashboard → comités
```

---

## Flujo del digitador

1. Login → `seleccionar_candidato.php` (solo digitadores)
2. Selecciona candidato de su partido (agrupados por cargo con foto y zona)
3. Dashboard muestra candidato activo en sidebar
4. Crea comités ligados a ese candidato
5. Impresión muestra logo del partido + foto del candidato

---

## Tablas MySQL

| Tabla | Descripción |
|---|---|
| `partidos` | nombre, siglas, logo BLOB, color_primario, color_sidebar, color_accent |
| `candidatos` | partido_id, nombre, cargo ENUM, descripcion (zona), foto BLOB |
| `usuarios` | rol ENUM(superadmin/owner/admin/usuario), partido_id |
| `comites` | candidato_id, creado_por |
| `coordinadores` / `miembros` | cedula, foto, recinto, colegio |
| `comite_coordinador` / `comite_miembro` | relaciones |

---

## Partidos preconfigurados

| Siglas | Color | Sidebar |
|---|---|---|
| PRM | #2563eb | #0d1b2a |
| PLD | #7c3aed | #1e1035 |
| FDP | #16a34a | #0a1f12 |

---

## Variables de entorno Dokploy

```
MYSQL_HOST=<host interno>
MYSQL_USER=<usuario>
MYSQL_PASS=<contraseña>
MYSQL_DATABASE=<nombre bd>

DB_HOST=148.0.129.233
DB_PORT=1433
DB_DATABASE=dbPRM
DB_USERNAME=kmota
DB_PASSWORD=@Gucci1826
DB_ENCRYPT=false
DB_TRUST_SERVER_CERTIFICATE=true
```

---

## Páginas del sistema

| Página | Rol | Función |
|---|---|---|
| `login.php` | Todos | Logo `logo_sistema.png` en panel oscuro |
| `seleccionar_candidato.php` | Digitador | Selección de candidato antes del dashboard |
| `dashboard.php` | Todos | Stats, charts, actividad reciente |
| `comites.php` | Todos | Listado con filtros |
| `crear_comite.php` | Todos | Crea comité (guarda candidato_id) |
| `editar_comite.php` | Todos | Editar + miembros/coordinador por cédula |
| `ver_comite.php` | Todos | Vista + impresión con logo y foto candidato |
| `config.php` | Owner | Logo → ColorThief auto-colores, candidatos |
| `partidos.php` | Superadmin | CRUD partidos + crear owners con partido |
| `usuarios.php` | Admin/Owner | Gestión usuarios del partido |
| `perfil.php` | Todos | Perfil + cambio contraseña |
| `consultar.html` | Todos | Consulta cédula → `api/consulta.php` |

---

## Tema dinámico

- `header.php` → `cargarTemaPartido()` → CSS vars desde BD por partido
- Mobile: FAB flotante (estilo WhatsApp) → bottom sheet con swipe-down
- Desktop: sidebar fijo oscuro con candidato activo + botón cambiar

---

## Migración BD (ejecutar en Dokploy si BD ya existe)

Archivo: `db/migration.sql`

Comandos clave:
```sql
ALTER TABLE usuarios ADD COLUMN partido_id INT NULL;
ALTER TABLE usuarios MODIFY COLUMN rol ENUM('superadmin','owner','admin','usuario') DEFAULT 'usuario';
CREATE TABLE IF NOT EXISTS partidos (...);
CREATE TABLE IF NOT EXISTS candidatos (...);  -- incluye campo descripcion
ALTER TABLE comites ADD COLUMN candidato_id INT NULL;
UPDATE usuarios SET rol='superadmin' WHERE usuario='admin';
UPDATE usuarios SET partido_id=1 WHERE usuario='admin';
```

---

## Credenciales por defecto

- **superadmin** / `Gucci1826`

---

## Estado actual
Sistema completo en producción. Pendiente: configurar candidatos desde `config.php`.
