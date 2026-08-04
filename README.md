# Sistema de Gestión de Ventas Masivas (ETL & Analytics)

Esta aplicación robusta en Laravel 12 y PHP 8.2+ está diseñada para la importación masiva de transacciones de ventas mediante archivos CSV, su procesamiento asíncrono eficiente y la visualización de reportes BI (Business Intelligence) en tiempo real mediante un Dashboard interactivo.

---

## 🛠️ Tecnologías Utilizadas

### Backend
| Tecnología | Versión | Uso |
|------------|---------|-----|
| **PHP** | 8.4 | Lenguaje principal del backend |
| **Laravel** | 12.x | Framework PHP para API REST y lógica de negocio |
| **MySQL** | 8.4 | Base de datos relacional principal |
| **Redis** | 7.x (Alpine) | Broker de colas y caché |
| **Laravel Queues** | - | Procesamiento asíncrono de archivos CSV |
| **Laravel Sail** | - | Entorno de desarrollo con Docker |

### Frontend
| Tecnología | Versión | Uso |
|------------|---------|-----|
| **Blade** | - | Motor de plantillas de Laravel |
| **Tailwind CSS** | 3.x | Estilos y diseño responsive |
| **JavaScript Vanilla** | ES6 | Lógica del dashboard (fetch, eventos, manipulación DOM) |
| **Vite** | 5.x | Bundler y compilación de assets |

### Infraestructura
| Tecnología | Versión | Uso |
|------------|---------|-----|
| **Docker** | 24.x | Contenerización de servicios |
| **Docker Compose** | 2.x | Orquestación de contenedores |
| **Laravel Sail** | - | Entorno de desarrollo optimizado |

### Herramientas de Desarrollo
| Tecnología | Versión | Uso |
|------------|---------|-----|
| **Composer** | 2.x | Gestor de dependencias PHP |
| **NPM** | 10.x | Gestor de dependencias JavaScript |
| **Git** | - | Control de versiones |

### APIs y Formatos
| Tecnología | Versión | Uso |
|------------|---------|-----|
| **REST API** | JSON | Comunicación frontend-backend |
| **CSV** | - | Formato de archivos de entrada |

---

## 1. Guía de Instalación y Ejecución

### Prerrequisitos
- **Docker** y **Docker Compose** instalados en tu sistema local.
- **Git** (opcional, para clonar).

### Pasos para el Despliegue Local

1. **Clonar e ingresar al directorio del proyecto:**
   ```bash
   cd ~/pruebatec/retail-etl
   ```

2. **Configurar el archivo de entorno (.env):**
   Copia el archivo de configuración de ejemplo:
   ```bash
   cp .env.example .env
   ```

3. **Iniciar el entorno con Laravel Sail:**
   Levanta los servicios en segundo plano (MySQL, Redis, etc.):
   ```bash
   ./vendor/bin/sail up -d
   ```

4. **Instalar dependencias de PHP:**
   Descarga todas las dependencias necesarias mediante Composer:
   ```bash
   ./vendor/bin/sail composer install
   ```

5. **Generar la clave de la aplicación:**
   ```bash
   ./vendor/bin/sail php artisan key:generate
   ```

6. **Ejecutar migraciones de base de datos:**
   Crea la estructura de tablas e índices requeridos:
   ```bash
   ./vendor/bin/sail php artisan migrate
   ```

7. **Instalar y compilar dependencias del Frontend:**
   Instala Node modules y compila los assets (CSS/JS):
   ```bash
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run build
   ```

8. **Iniciar el procesador de tareas (Queue Worker):**
   *Importante*: Para que los archivos CSV se procesen en segundo plano, debes dejar un worker escuchando la cola de tareas. El contenedor `queue-worker` debería correr automáticamente al iniciar el proyecto con `./vendor/bin/sail up -d`.

   Verificar que el worker está corriendo:
   ```bash
   ./vendor/bin/sail ps
   ```
   Deberías ver el servicio `queue-worker` en estado `Up` o `running`.

   Si el worker no está corriendo, iniciarlo manualmente:
   ```bash
   ./vendor/bin/sail php artisan queue:work
   ```

La aplicación estará accesible en tu navegador en [http://localhost](http://localhost).

---

## 2. Decisiones Técnicas y Arquitectura

El sistema ha sido estructurado siguiendo principios sólidos de rendimiento, mantenibilidad y escalabilidad. A continuación se detallan las decisiones técnicas clave aplicadas:

### A. Procesamiento de Archivos Eficiente (ETL)
Para procesar archivos de hasta 100,000 registros sin afectar el rendimiento del servidor ni la experiencia del usuario, se implementaron las siguientes estrategias:

*   **Procesamiento Asíncrono (Queues/Jobs):** El endpoint `POST /api/imports` no procesa el archivo de forma síncrona. En su lugar:
    1. Valida el archivo CSV subido.
    2. Guarda el archivo físicamente en el storage local.
    3. Crea un registro en la tabla `imports` con estado `pending`.
    4. Despacha el Job `ProcessCsvImport` a la cola de procesamiento.
    5. Retorna inmediatamente una respuesta HTTP `202 Accepted` con los detalles de la carga, garantizando una excelente experiencia de usuario.
*   **Lectura en Stream (Bajo consumo de memoria):** En lugar de cargar todo el CSV a la memoria RAM (lo cual causaría desbordamientos con archivos grandes), el Job abre el archivo usando `fopen` y lee línea por línea con `fgetcsv`. Esto mantiene un consumo de memoria constante de **O(1)**.
*   **Inserción en Lotes (Batch Inserts):** En lugar de realizar una consulta `INSERT` en la base de datos por cada fila (lo cual saturaría el canal I/O), los registros válidos se acumulan en memoria y se insertan en lotes de 500 (`Sale::insert($salesBatch)`). Lo mismo se realiza con los registros que presentan inconsistencias (`ImportError::insert($errorsBatch)`).
*   **Transaccionalidad (ACID):** El Job envuelve el proceso de procesamiento de cada lote en transacciones de base de datos. Si ocurre un fallo catastrófico en el servidor, se ejecuta un rollback para asegurar que la base de datos no quede en un estado corrupto.
*   **Validación no Bloqueante:** Cada fila pasa por validaciones específicas (fechas correctas, precios no negativos, cantidades mayores a cero, campos obligatorios no vacíos). Las filas inválidas no interrumpen la importación; se omiten y se guardan en la tabla `import_errors` registrando el número de fila exacto y el motivo del fallo para su posterior visualización.

### B. Estrategia de Rapidez en la Generación de Reportes
El cálculo de reportes agregados para grandes volúmenes de datos puede ser lento si no se diseña correctamente la base de datos:

*   **Índices Compuestos de Base de Datos:** En la migración de la tabla `sales` se crearon índices compuestos estratégicos basados en los patrones de consulta comunes para el reporte:
    *   `['import_id', 'product_name']`
    *   `['import_id', 'category']`
    *   `['import_id', 'country']`
    *   Esto permite al motor de base de datos filtrar y agrupar (usando `GROUP BY`) de manera extremadamente veloz, transformando escaneos completos de tablas (Seq Scans) en búsquedas indexadas de alta velocidad.
*   **Precalculación de Columnas:** El campo `total` (fórmula `quantity * unit_price * (1 - discount)`) se calcula del lado del servidor PHP durante la etapa de ingesta y se persiste directamente en la columna `total`. Esto evita tener que calcular operaciones aritméticas al vuelo para cada registro durante la ejecución de las consultas analíticas del reporte.

### C. Arquitectura del Frontend (Dashboard)
El dashboard sigue un enfoque híbrido pragmático que equilibra simplicidad y mantenibilidad:

*   **Plantilla Única (Blade):** Todo el HTML está definido en un único archivo `dashboard.blade.php`, facilitando la modificación de estilos y estructura por parte de diseñadores.
*   **Lógica en JavaScript Vanilla:** El archivo `dashboard.js` maneja toda la lógica de negocio:
    *   Llamadas a la API REST (`fetch`)
    *   Renderizado dinámico de tablas
    *   Actualización de estadísticas en tiempo real
    *   Manejo de eventos (subir archivo, ver errores, generar reportes)
*   **Actualización en Tiempo Real:** El dashboard se actualiza automáticamente cada 30 segundos mediante `setInterval`, manteniendo la información siempre actualizada sin necesidad de recargar la página.

### D. Gestión de Errores y Feedback
*   **Modales Informativos:** Los errores de importación se muestran en un modal con detalles específicos (número de fila, mensaje de error).
*   **Alertas en Tiempo Real:** Feedback visual inmediato al subir archivos (éxito/error).
*   **Estados Claros:** Cada importación muestra su estado visualmente (completado, procesando, fallido, con errores).

---

## 3. Propuesta Técnica ante Escalabilidad (Millones de Registros)

Si el volumen de datos escala a millones de registros por archivo, la arquitectura propuesta se adaptaría mediante las siguientes mejoras:

1.  **División de Archivos (File Chunking):**
    En lugar de despachar un único Job para un archivo de 5 millones de registros, el archivo se dividirá inicialmente en el servidor web en sub-archivos más pequeños. Cada sub-archivo despachará un Job independiente a la cola.
2.  **Paralelización de Workers:**
    Al dividir el archivo, múltiples Queue Workers independientes (escalados horizontalmente en contenedores o pods de Kubernetes) pueden procesar los sub-archivos de forma paralela en la cola (usando Redis como broker), reduciendo el tiempo de procesamiento lineal de forma drástica.
3.  **Caché Analítica para Reportes:**
    Una vez que una importación finaliza (`status` cambia a `completed`), sus registros ya no cambian. El backend calculará el reporte una única vez y lo almacenará en un almacén de caché como **Redis** de forma indefinida con la clave `report_summary_{import_id}`. Cualquier consulta subsiguiente al endpoint del reporte retornará la respuesta desde caché en microsegundos, eliminando la carga de consultas SQL a la base de datos.
4.  **Bases de Datos Columnares / Motores OLAP:**
    Para análisis en tiempo real sobre millones de filas, se puede migrar la tabla de reportes a un motor de base de datos columnar como **ClickHouse**. Las bases de datos relacionales tradicionales como MySQL/PostgreSQL pueden saturarse ante agregaciones complejas sobre tablas gigantescas.
5.  **Cargas de Alta Velocidad (Bulk Loading nativo):**
    En lugar de usar inserciones en lote a través del ORM Eloquent, se puede utilizar el cargador masivo nativo del motor de base de datos, como `LOAD DATA INFILE` en MySQL o `COPY` en PostgreSQL, que cargan archivos estructurados directamente a nivel de almacenamiento del motor a velocidades de disco.
