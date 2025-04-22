# Heartize™ – Agencia Creativa Fullstack

¿Necesitas un proyecto a medida?  
Creamos experiencias digitales únicas: sitios web impactantes, tiendas online con estilo, sistemas personalizados que enamoran y marcas que dejan huella.

🎯 Desarrollo Web | 🎨 Diseño Gráfico | 🛍️ E-commerce | ⚙️ SaaS

🔗 [Descubre lo que podemos hacer juntos](https://www.heartize.com)

#############################################################

<body>
  <input type="checkbox" id="menu-toggle">
  <label for="menu-toggle">☰ Documentación Técnica</label>
  <nav>
    <h1>Índice</h1><br>
    <a href="#intro">Introducción</a><br>
    <a href="#estructura">Estructura</a><br>
    <a href="#req">Requisitos</a><br>
    <a href="#instalacion">Instalación</a><br>
    <a href="#config">Configuración</a><br>
    <a href="#uso">Guía de Uso</a><br>
    <a href="#modulos">Módulos</a><br>
    <a href="#modelos">Modelos</a><br>
    <a href="#vistas">Vistas</a><br>
    <a href="#api">API Traducción</a><br>
    <a href="#assets">Assets</a><br>
    <a href="#includes">Includes</a><br>
    <a href="#db">Base de Datos</a><br>
    <a href="#scripts">Scripts</a><br>
    <a href="#security">Seguridad</a><br>
    <a href="#extender">Extender</a><br>
    <a href="#faq">FAQ</a><br>
  </nav>
  <main>
    <section id="intro">
      <h1>Introducción</h1>
      <p>Bienvenido a la documentación completa de <strong>Akaunting PHP</strong>. Aquí encontrarás descripción, configuración, uso diario, y pautas de desarrollo y mantenimiento.</p>
    </section>
    <section id="estructura">
      <h1>Estructura del Proyecto</h1>
      <p>Organización de carpetas y archivos:</p>
      <pre>📂 Estructura del Proyecto
├── .htaccess
├── AkauntingPHP.txt
├── LICENSE
├── README.md
├── akaunting_php.sql
├── akaunting_php.zip
├── api
│   └── translate.php
├── assets
│   ├── css
│   │   └── styles.css
│   └── js
│       └── app.js
├── check_files.php
├── config
│   ├── config.php
│   └── database.php
├── controllers
│   ├── AccountController.php
│   ├── AuthController.php
│   ├── BillController.php
│   ├── CategoryController.php
│   ├── ClientController.php
│   ├── CustomFieldController.php
│   ├── DashboardController.php
│   ├── InvoiceController.php
│   ├── InvoiceTemplateController.php
│   ├── MenuPermissionController.php
│   ├── ProductController.php
│   ├── ReconciliationController.php
│   ├── ReportController.php
│   ├── SettingController.php
│   ├── TransactionController.php
│   ├── UserController.php
│   ├── VendorController.php
│   └── WarehouseController.php
├── create_views.php
├── dashboard
│   └── index.php
├── database
│   └── database.txt
├── debug.php
├── documentation.html
├── generate_tree.php
├── includes
│   ├── auth.php
│   ├── functions.php
│   └── helpers.php
├── index.php
├── install.php
├── logs
│   ├── logs.txt
│   └── php_error.log
├── models
│   ├── AccountModel.php
│   ├── BillItemModel.php
│   ├── BillModel.php
│   ├── CategoryModel.php
│   ├── ClientModel.php
│   ├── CustomFieldModel.php
│   ├── InvoiceItemModel.php
│   ├── InvoiceModel.php
│   ├── InvoiceTemplateModel.php
│   ├── MenuPermissionModel.php
│   ├── ProductCategoryModel.php
│   ├── ProductModel.php
│   ├── ReconciliationModel.php
│   ├── SettingsModel.php
│   ├── TransactionModel.php
│   ├── VendorModel.php
│   └── WarehouseModel.php
├── schema.sql
├── uploads
│   ├── logo.png
│   └── logo_1745168240.png
└── views
    ├── accounts
    │   ├── form.php
    │   ├── index.php
    │   └── show.php
    ├── bills
    │   ├── form.php
    │   ├── index.php
    │   ├── print.php
    │   └── show.php
    ├── categories
    │   ├── form.php
    │   └── index.php
    ├── clients
    │   ├── form.php
    │   ├── index.php
    │   └── show.php
    ├── custom_fields
    │   ├── create.php
    │   ├── edit.php
    │   ├── form.php
    │   ├── index.php
    │   ├── render.php
    │   ├── show.php
    │   ├── store.php
    │   └── update.php
    ├── dashboard
    │   └── index.php
    ├── errors
    │   ├── 404.php
    │   └── 500.php
    ├── helpers
    │   ├── backup.php
    │   ├── export_import.php
    │   └── ui.php
    ├── invoice_templates
    │   ├── form.php
    │   ├── index.php
    │   └── preview.php
    ├── invoices
    │   ├── form.php
    │   ├── index.php
    │   ├── print.php
    │   └── show.php
    ├── layouts
    │   ├── footer.php
    │   ├── header.php
    │   ├── login.php
    │   ├── notifications.php
    │   ├── register.php
    │   └── sidebar.php
    ├── products
    │   ├── form.php
    │   ├── index.php
    │   └── show.php
    ├── reconciliations
    │   ├── form.php
    │   ├── index.php
    │   ├── print.php
    │   └── show.php
    ├── reports
    │   ├── expense.php
    │   ├── income.php
    │   ├── profit_loss.php
    │   └── tax.php
    ├── settings
    │   ├── backups.php
    │   ├── company.php
    │   ├── email.php
    │   ├── invoice.php
    │   ├── menu_permissions.php
    │   ├── settings_menu.php
    │   ├── taxes.php
    │   └── warehouses.php
    ├── transactions
    │   ├── form.php
    │   ├── index.php
    │   ├── show.php
    │   └── transfer.php
    ├── users
    │   ├── change_password.php
    │   ├── form.php
    │   ├── index.php
    │   └── profile.php
    ├── vendors
    │   ├── form.php
    │   ├── index.php
    │   └── show.php
    └── warehouses
        ├── form.php
        └── index.php</pre>
    </section>
    <section id="req">
      <h1>Requisitos del Sistema</h1>
      <ul>
        <li>PHP >= 8.1.31 (CLI y módulo)</li>
        <li>MySQL >= 5.7</li>
        <li>Apache/Nginx con .htaccess habilitado</li>
        <li>Extensiones: PDO, cURL, mbstring, OpenSSL</li>
        <li>Permisos 775 en <code>logs/</code> y <code>uploads/</code></li>
      </ul>
    </section>
    <section id="instalacion">
      <h1>Instalación Paso a Paso</h1>
      <ol>
        <li>Descomprime <code>akaunting_php.zip</code> en tu directorio web.</li>
        <li>Renombra <code>config/config.php.example</code> a <code>config/config.php</code> y ajusta tus valores.</li>
        <li>Actualiza <code>config/database.php</code> con credenciales de la base de datos.</li>
        <li>Importa <code>schema.sql</code> (o <code>akaunting_php.sql</code>) vía phpMyAdmin o CLI.</li>
        <li>Ejecuta <code>install.php</code> y sigue el asistente.</li>
        <li>Elimina <code>install.php</code> tras la instalación (seguridad).</li>
        <li>Verifica logs en <code>logs/php_error.log</code> para confirmar ausencia de errores.</li>
      </ol>
      <h2>Resolución de Problemas Comunes</h2>
      <ul>
        <li><strong>Error 500:</strong> Revisa <code>logs/php_error.log</code>.</li>
        <li><strong>Conexión BD fallida:</strong> Valida host/usuario/contraseña.</li>
        <li><strong>Permisos:</strong> Ajusta chmod 775 en carpetas de subida y logs.</li>
      </ul>
    </section>
    <section id="config">
      <h1>Configuración Detallada</h1>
      <h2>config/config.php</h2>
      <pre>&lt;?php
return [
  'app_name' => 'Akaunting PHP',
  'base_url' => 'http://ejemplo.com/',
  'timezone' => 'Europe/Madrid',
  'debug'    => true
];</pre>
      <h2>config/database.php</h2>
      <pre>&lt;?php
return [
  'host' => 'localhost',
  'user' => 'root',
  'pass' => 'secreto',
  'name' => 'akaunting_db',
  'port' => 3306
];</pre>
    </section>
    <section id="uso">
      <h1>Guía de Uso Diario</h1>
      <h2>Inicio de Sesión</h2>
      <p>Abre <code>index.php</code>, ingresa credenciales y accede al Dashboard.</p>
      <h2>Dashboard</h2>
      <p>Visualiza métricas clave: ingresos, gastos, facturas pendientes y notificaciones.</p>
      <h2>Gestión de Clientes</h2>
      <ol>
        <li>Menú Lateral → Clientes</li>
        <li>Crear / Editar / Eliminar registros</li>
        <li>Buscador y filtros avanzados</li>
      </ol>
      <h2>Facturación</h2>
      <ol>
        <li>Menú → Facturas</li>
        <li>Form para nueva factura con items, impuestos y descuentos</li>
        <li>Generar PDF e imprimir desde <code>print.php</code></li>
      </ol>
      <h2>Reportes</h2>
      <ul>
        <li>Ingresos vs. Gastos</li>
        <li>Reporte de Impuestos</li>
        <li>Pérdidas y Ganancias</li>
      </ul>
      <h2>Configuración Avanzada</h2>
      <p>En Settings puedes definir moneda, prefijos, backups y roles de usuario.</p>
    </section>
    <section id="modulos">
      <h1>Módulos & Controladores</h1>
      <p>Patrón CRUD en <code>controllers/</code>. Ejemplo: <code>ClientController.php</code></p>
      <pre>&lt;?php
class ClientController {
  public function index() { /* Listado */ }
  public function create() { /* Formulario */ }
  public function store() { /* Guardar */ }
  public function show($id) { /* Detalle */ }
  public function edit($id) { /* Editar */ }
  public function update($id) { /* Actualizar */ }
  public function destroy($id) { /* Eliminar */ }
}
</pre>
    </section>
    <section id="modelos">
      <h1>Modelos</h1>
      <p>Ubicados en <code>models/</code>, extienden conexión PDO y ofrecen métodos:</p>
      <ul>
        <li><code>getAll()</code>, <code>find($id)</code></li>
        <li><code>create($data)</code>, <code>update($id,$data)</code></li>
        <li><code>delete($id)</code></li>
      </ul>
    </section>
    <section id="vistas">
      <h1>Vistas</h1>
      <p>Arquitectura MVC Básico: <code>views/entidad/*.php</code></p>
      <ul>
        <li><code>index.php</code> &mdash; Listado</li>
        <li><code>form.php</code> &mdash; Crear/Editar</li>
        <li><code>show.php</code> &mdash; Detalle</li>
      </ul>
      <p>Layout global: <code>views/layouts/header.php</code>, <code>sidebar.php</code>, <code>footer.php</code>.</p>
    </section>
    <section id="api">
      <h1>API de Traducción</h1>
      <p><code>api/translate.php</code> expone un endpoint REST:</p>
      <pre>POST /api/translate.php
{ "text":"hola mundo","lang":"en" }
=&gt; { "translated":"hello world" }</pre>
      <p>Incluye caching local en <code>includes/functions.php</code>.</p>
    </section>
    <section id="assets">
      <h1>Assets (CSS/JS)</h1>
      <p>Global en <code>assets/css/styles.css</code> y <code>assets/js/app.js</code>:</p>
      <ul>
        <li>Menú responsivo</li>
        <li>Toasts de notificaciones</li>
        <li>Validaciones frontend</li>
      </ul>
    </section>
    <section id="includes">
      <h1>Archivos Comunes</h1>
      <ul>
        <li><code>auth.php</code>: sesiones y permisos</li>
        <li><code>functions.php</code>: UTILS, envío de email, logging</li>
        <li><code>helpers.php</code>: formateo fechas, números</li>
      </ul>
    </section>
    <section id="db">
      <h1>Base de Datos</h1>
      <p>Esquema en <code>schema.sql</code>. Tablas principales:</p>
      <ul>
        <li><strong>users</strong>, <strong>clients</strong>, <strong>invoices</strong></li>
        <li><strong>invoice_items</strong>, <strong>transactions</strong></li>
        <li><strong>settings</strong>, <strong>logs</strong></li>
      </ul>
      <p>Relaciones:</p>
      <ul>
        <li><code>invoices.client_id → clients.id</code></li>
        <li><code>invoice_items.invoice_id → invoices.id</code></li>
      </ul>
    </section>
    <section id="scripts">
      <h1>Scripts Auxiliares</h1>
      <ul>
        <li><code>generate_tree.php</code>: muestra estructura de carpetas.</li>
        <li><code>check_files.php</code>: comprueba archivos críticos.</li>
        <li><code>debug.php</code>: activa modo debug completo.</li>
      </ul>
    </section>
    <section id="security">
      <h1>Seguridad y Mantenimiento</h1>
      <h2>Respaldos Automáticos</h2>
      <p>Usa <code>mysqldump</code> en cron diario:</p>
      <pre>0 2 * * *   mysqldump -u root -p secreta akaunting_db &gt; backups/backup_$(date +\%F).sql</pre>
      <h2>Rotación de Logs</h2>
      <p>Configura <code>logrotate</code> para no saturar <code>logs/</code>.</p>
      <h2>Actualización del Sistema</h2>
      <ol>
        <li>Respaldar archivos y BD.</li>
        <li>Reemplazar carpeta del proyecto con nueva versión.</li>
        <li>Ejecutar migraciones (si aplica).</li>
        <li>Verificar logs y funcionalidades.</li>
      </ol>
      <h2>Permisos y Roles</h2>
      <p>Define roles en tabla <code>settings</code> y gestiona en <code>SettingController</code>.</p>
    </section>
    <section id="extender">
      <h1>Cómo Extender</h1>
      <ol>
        <li>Crear Controller, Model y Vistas (index, form, show).</li>
        <li>Añadir ruta en <code>views/layouts/sidebar.php</code>.</li>
        <li>Actualizar <code>assets/js/app.js</code> si necesita lógica.</li>
        <li>Registrar modelo en <code>includes/functions.php</code> (opcional).</li>
      </ol>
    </section>
    <section id="faq">
      <h1>FAQ</h1>
      <h2>Restablecer Contraseña</h2>
      <p>En BD, tabla <code>users</code>, actualizar con <code>password_hash('nueva', PASSWORD_DEFAULT)</code>.</p>
      <h2>Cambiar Idioma</h2>
      <p>Modificar <code>'timezone'</code> en <code>config/config.php</code>.</p>
      <h2>Errores Comunes</h2>
      <ul>
        <li>503 ó 500: revisar permisos y módulos PHP.</li>
        <li>PDOException: validar credenciales BD.</li>
      </ul>
    </section>
  </main>
  <script>
    document.addEventListener('DOMContentLoaded', ()=>{
      const sections = document.querySelectorAll('main section');
      const navLinks = document.querySelectorAll('nav a');
      const observer = new IntersectionObserver(entries=>{
        entries.forEach(entry=>{
          if(entry.isIntersecting){
            const id=entry.target.id;
            navLinks.forEach(link=>link.classList.remove('active'));
            document.querySelector(`nav a[href=\"#${id}\"]`).classList.add('active');
          }
        });
      }, { threshold:0.6 });
      sections.forEach(sec=>observer.observe(sec));
    });
  </script>
</body>
</html>
