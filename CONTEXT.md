# Contexto del Proyecto — Sistema de Comités PRM

## ¿Qué es?
Sistema web de gestión de comités afectivos del Partido Revolucionario Moderno (PRM), República Dominicana.

## Repositorio
https://github.com/gucci7up/comites2028.git

## Stack
- PHP 8.3 + Apache (Docker)
- MySQL — base de datos propia del sistema (gestionada por Dokploy)
- SQL Server externo — padrón electoral y fotos PRM (solo lectura)

## Arquitectura de base de datos

### MySQL (tablas propias)
| Tabla | Uso |
|---|---|
| `usuarios` | Usuarios del sistema (admin/usuario) |
| `comites` | Comités por municipio/circunscripción |
| `coordinadores` | Coordinadores con cédula, foto, recinto, colegio |
| `miembros` | Miembros con datos similares |
| `comite_coordinador` | Relación comité ↔ coordinador |
| `comite_miembro` | Relación comité ↔ miembro |

### SQL Server externo (solo lectura)
- Servidor: `148.0.129.233:1433`
- Bases: `dbPRM` y `dbPadronFeb2024`
- Uso: consulta de padrón electoral y fotos por cédula
- Función principal: `buscarPersonaPorCedula()` en `includes/funciones.php`
- API: `api/consulta.php`

## Variables de entorno requeridas en Dokploy

```
# MySQL (Dokploy lo gestiona — usar los datos que da Dokploy al crear la BD)
MYSQL_HOST=<host interno Dokploy>
MYSQL_USER=<usuario>
MYSQL_PASS=<contraseña>
MYSQL_DATABASE=<nombre de la bd>

# SQL Server externo
DB_HOST=148.0.129.233
DB_PORT=1433
DB_DATABASE=dbPRM
DB_USERNAME=kmota
DB_PASSWORD=@Gucci1826
DB_ENCRYPT=false
DB_TRUST_SERVER_CERTIFICATE=true
```

## Despliegue — Dokploy
- Tipo: Docker Compose
- Rama: `master`
- El `docker-compose.yml` solo tiene el servicio `app` (sin MySQL local)
- MySQL se crea como servicio separado en Dokploy y se conecta vía env vars
- El schema de la BD se importa manualmente desde `db/comites_prm.sql`

## Archivos clave modificados para producción
| Archivo | Cambio |
|---|---|
| `Dockerfile` | PHP 8.3 + Apache + mysqli + pdo_sqlsrv + ODBC Driver 18 |
| `docker-compose.yml` | Solo servicio `app`, sin MySQL local |
| `db/config.php` | Lee env vars: `MYSQL_*` para MySQL, `DB_*` para SQL Server |
| `api/consulta.php` | Usa `conectarSQLServer()` del config central (sin credenciales hardcodeadas) |
| `login.php` | Usa `MYSQL_*` env vars |
| `db/setup.php` | Usa `MYSQL_*` env vars |
| `db/comites_prm.sql` | Sin `CREATE DATABASE` ni `USE` para importar en Dokploy |
| `.gitignore` | Excluye `.env` y archivos `.rar` |

## Credenciales por defecto del sistema
- Usuario: `admin`
- Contraseña: `admin123`

## Notas importantes
- El build tarda más la primera vez porque instala el Microsoft ODBC Driver 18
- PHP 8.3 es requerido — las versiones de `sqlsrv`/`pdo_sqlsrv` en PECL ya no soportan 8.2
- La carpeta `fotos/` está en `.gitignore` (excepto `default.svg`) — las fotos se almacenan como BLOB en la BD
