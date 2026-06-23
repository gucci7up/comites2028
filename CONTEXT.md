# Comités Afectivo 2028 — Contexto del Proyecto

## Sistema
SaaS de gestión de comités afectivos para partidos políticos de República Dominicana.
Un servidor por partido. Vendible a cualquier partido.

**Repo:** https://github.com/gucci7up/comites2028.git | **Dominio:** comitesprm.com
**Stack:** PHP 8.3 + Apache (Docker) | Bootstrap 5 | Inter font | Chart.js | ColorThief.js
**Deploy:** Dokploy + MySQL managed + SQL Server externo `148.0.129.233:1433`

---

## Roles

| Rol | Acceso |
|---|---|
| `admin` | Todo + Configuración (logo, colores, candidatos) |
| `supervisor` | Comités + Usuarios |
| `digitador` | Selecciona candidato → crea comités |

---

## Flujo digitador

Login → `seleccionar_candidato.php` → elige candidato (Presidente/Senador/Diputado/Alcalde/Regidor) → Dashboard con contexto → crea comités → imprime (logo partido + foto candidato)

---

## Base de datos

| Tabla | Descripción |
|---|---|
| `configuracion` | Fila única: nombre_partido, siglas, logo BLOB, color_primario, color_sidebar, color_accent |
| `candidatos` | nombre, cargo ENUM, descripcion (zona), foto BLOB, activo |
| `usuarios` | rol ENUM(admin/supervisor/digitador) |
| `comites` | candidato_id, creado_por |
| `coordinadores` / `miembros` | cedula, foto, recinto, colegio |

---

## Variables de entorno

```
MYSQL_HOST / MYSQL_USER / MYSQL_PASS / MYSQL_DATABASE
DB_HOST=148.0.129.233 / DB_PORT=1433 / DB_DATABASE=dbPRM
DB_USERNAME=kmota / DB_PASSWORD=@Gucci1826
DB_ENCRYPT=false / DB_TRUST_SERVER_CERTIFICATE=true
```

---

## UI/UX

**Tema dinámico:** `cargarConfiguracion()` en `header.php` lee BD → inyecta CSS vars. CERO colores hardcodeados. Todo usa `var(--accent)`, `var(--accent-light)`, `var(--sidebar-bg)`.

**Presets en config.php:** Azul PRM `#2563eb`, Morado PLD `#7c3aed`, Verde FDP `#16a34a`

**ColorThief.js:** extrae colores del logo automáticamente al subir imagen.

**Dashboard (Coursue-style):**
- 3 columnas: sidebar fijo | contenido principal | panel derecho (296px)
- Hero banner gradiente + progress cards (3) + comité cards grid + tabla Mis Comités
- Panel derecho: anillo progreso circular + mini bar chart municipios + miembros recientes

**Mobile:** pill nav bar flotante bottom-center (estilo Instagram/TikTok) con 5 iconos. Icono avatar abre bottom sheet con swipe-down.

---

## Páginas

| Página | Rol | Descripción |
|---|---|---|
| `login.php` | Todos | Panel oscuro + logo1.png |
| `seleccionar_candidato.php` | Digitador | Grid candidatos por cargo |
| `dashboard.php` | Todos | Coursue-style layout |
| `comites.php` | Todos | Filter + tabla |
| `crear_comite.php` | Todos | Form + step guide |
| `editar_comite.php` | Todos | Edit + miembros/coordinador |
| `ver_comite.php` | Todos | Vista + print con candidato |
| `config.php` | Admin | Logo + ColorThief + candidatos |
| `usuarios.php` | Admin/Sup | CRUD con role badges |
| `perfil.php` | Todos | Avatar + form |
| `consultar.html` | Todos | Cédula → api/consulta.php |

---

## Credenciales

- `admin` / `Gucci1826`

---

## Notas importantes

- `color-mix(in srgb, var(--accent) X%, white)` se usa en toda la app para tints del color del partido
- `cargarConfiguracion()` usa `static $config` para cachear la query por request
- `esAdmin()` incluye admin. `esSupervisor()` incluye admin + supervisor.
- Digitadores sin candidato → redirigen a `seleccionar_candidato.php`
- `candidato_id` en sesión: `candidato_id`, `candidato_nombre`, `candidato_cargo`, `candidato_desc`
