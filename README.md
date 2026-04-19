# 📊 Estructura de Base de Datos - Sistema de Cotizaciones

## �️ Script SQL de Creación

```sql
-- Crear base de datos
CREATE DATABASE IF NOT EXISTS sistema_cotizaciones 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;

USE sistema_cotizaciones;

-- Tabla: asignaciones
CREATE TABLE `asignaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_ot` int(11) DEFAULT NULL,
  `id_vehiculo` int(11) DEFAULT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `id_asociado` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_ot` (`id_ot`),
  KEY `id_vehiculo` (`id_vehiculo`),
  KEY `id_empleado` (`id_empleado`),
  KEY `id_asociado` (`id_asociado`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `asignaciones_ibfk_1` FOREIGN KEY (`id_ot`) REFERENCES `ordenes_trabajo` (`id`),
  CONSTRAINT `asignaciones_ibfk_2` FOREIGN KEY (`id_vehiculo`) REFERENCES `flota` (`id`),
  CONSTRAINT `asignaciones_ibfk_3` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id`),
  CONSTRAINT `asignaciones_ibfk_4` FOREIGN KEY (`id_asociado`) REFERENCES `asociados` (`id`),
  CONSTRAINT `asignaciones_ibfk_5` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: asociados
CREATE TABLE `asociados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `NOMBRE` varchar(255) NOT NULL,
  `RUT` varchar(20) DEFAULT NULL,
  `TELEFONO` varchar(50) DEFAULT NULL,
  `EMAIL` varchar(255) DEFAULT NULL,
  `VEHICULO` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: cargos
CREATE TABLE `cargos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: clientes
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `NOMBRE` varchar(255) NOT NULL,
  `RUT` varchar(20) NOT NULL,
  `TIPO` varchar(50) DEFAULT NULL,
  `DIRECCION` varchar(500) DEFAULT NULL,
  `TELEFONO` varchar(50) DEFAULT NULL,
  `EMAIL` varchar(255) DEFAULT NULL,
  `GIRO` varchar(255) DEFAULT NULL,
  `credito` decimal(15,2) DEFAULT 300000.00,
  `credito_asignado` decimal(15,2) DEFAULT 300000.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `RUT` (`RUT`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: codigos_autorizacion
CREATE TABLE `codigos_autorizacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rut_cliente` varchar(20) DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `creado_por` varchar(255) DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  `estado` varchar(20) DEFAULT 'pendiente',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: comunas
CREATE TABLE `comunas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `region` varchar(255) DEFAULT NULL,
  `monto` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: costos_variables
CREATE TABLE `costos_variables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Tipo` varchar(50) DEFAULT NULL,
  `Nombre` varchar(255) DEFAULT NULL,
  `monto` decimal(15,2) DEFAULT NULL,
  `Descripcion` text DEFAULT NULL,
  `grupo` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: cotizaciones
CREATE TABLE `cotizaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rut_CLIENTE` varchar(20) DEFAULT NULL,
  `id_division` int(11) DEFAULT NULL,
  `id_servicio` int(11) DEFAULT NULL,
  `id_turno` int(11) DEFAULT NULL,
  `id_tipo_vehiculo` int(11) DEFAULT NULL,
  `id_sub_tipo` varchar(100) DEFAULT NULL,
  `id_comuna_origen` int(11) DEFAULT NULL,
  `id_comuna_destino` int(11) DEFAULT NULL,
  `direccion_origen` varchar(500) DEFAULT NULL,
  `direccion_destino` varchar(500) DEFAULT NULL,
  `tipo_camino` int(11) DEFAULT NULL,
  `tipo_direccion` varchar(100) DEFAULT NULL,
  `carga` varchar(100) DEFAULT NULL,
  `total_km` decimal(10,2) DEFAULT NULL,
  `monto_inicial` decimal(15,2) DEFAULT NULL,
  `monto_final` decimal(15,2) DEFAULT NULL,
  `monto_turno` decimal(15,2) DEFAULT NULL,
  `monto_tipo_camino` decimal(15,2) DEFAULT NULL,
  `credito_aplicado` decimal(15,2) DEFAULT NULL,
  `Descripcion` text DEFAULT NULL,
  `Panne` varchar(255) DEFAULT NULL,
  `base_salida` varchar(255) DEFAULT NULL,
  `nombre_solicitante` varchar(255) DEFAULT NULL,
  `email_CLIENTE` varchar(255) DEFAULT NULL,
  `telefono_CLIENTE` varchar(50) DEFAULT NULL,
  `coordinador_id` int(11) DEFAULT NULL,
  `forma_de_pago` varchar(100) DEFAULT NULL,
  `documento_tributario` varchar(100) DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'Pendiente',
  `Fecha_servicio` date DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `token_aprobacion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_division` (`id_division`),
  KEY `id_servicio` (`id_servicio`),
  KEY `id_turno` (`id_turno`),
  KEY `id_tipo_vehiculo` (`id_tipo_vehiculo`),
  KEY `id_comuna_origen` (`id_comuna_origen`),
  KEY `id_comuna_destino` (`id_comuna_destino`),
  KEY `coordinador_id` (`coordinador_id`),
  CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`id_division`) REFERENCES `division` (`id`),
  CONSTRAINT `cotizaciones_ibfk_2` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id`),
  CONSTRAINT `cotizaciones_ibfk_3` FOREIGN KEY (`id_turno`) REFERENCES `turno` (`id`),
  CONSTRAINT `cotizaciones_ibfk_4` FOREIGN KEY (`id_tipo_vehiculo`) REFERENCES `vehiculos` (`id`),
  CONSTRAINT `cotizaciones_ibfk_5` FOREIGN KEY (`id_comuna_origen`) REFERENCES `comunas` (`id`),
  CONSTRAINT `cotizaciones_ibfk_6` FOREIGN KEY (`id_comuna_destino`) REFERENCES `comunas` (`id`),
  CONSTRAINT `cotizaciones_ibfk_7` FOREIGN KEY (`coordinador_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: division
CREATE TABLE `division` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `NOMBRE` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: empleados
CREATE TABLE `empleados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rut` varchar(20) NOT NULL,
  `nombre_completo` varchar(255) NOT NULL,
  `celular` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `id_cargo` int(11) DEFAULT NULL,
  `Banco` varchar(100) DEFAULT NULL,
  `tipo_cuenta` varchar(100) DEFAULT NULL,
  `numero_cuenta` varchar(100) DEFAULT NULL,
  `centro_costo` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_cargo` (`id_cargo`),
  CONSTRAINT `empleados_ibfk_1` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: flota
CREATE TABLE `flota` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patente` varchar(20) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `codigo_movil` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: gastos
CREATE TABLE `gastos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_ot` int(11) DEFAULT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `id_asociado` int(11) DEFAULT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `monto` decimal(15,2) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_ot` (`id_ot`),
  KEY `id_empleado` (`id_empleado`),
  KEY `id_asociado` (`id_asociado`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `gastos_ibfk_1` FOREIGN KEY (`id_ot`) REFERENCES `ordenes_trabajo` (`id`),
  CONSTRAINT `gastos_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id`),
  CONSTRAINT `gastos_ibfk_3` FOREIGN KEY (`id_asociado`) REFERENCES `asociados` (`id`),
  CONSTRAINT `gastos_ibfk_4` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: observaciones
CREATE TABLE `observaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_ot` int(11) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_ot` (`id_ot`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `observaciones_ibfk_1` FOREIGN KEY (`id_ot`) REFERENCES `ordenes_trabajo` (`id`),
  CONSTRAINT `observaciones_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: ordenes_trabajo
CREATE TABLE `ordenes_trabajo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cotizacion` int(11) DEFAULT NULL,
  `Descripcion` text DEFAULT NULL,
  `Fecha_creacion` datetime DEFAULT current_timestamp(),
  `Fecha_Modificacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_usuario` int(11) DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'En programación',
  `info_marca` varchar(255) DEFAULT NULL,
  `info_modelo` varchar(255) DEFAULT NULL,
  `info_patente` varchar(100) DEFAULT NULL,
  `info_chofer` varchar(255) DEFAULT NULL,
  `info_telefono` varchar(50) DEFAULT NULL,
  `info_contacto` varchar(255) DEFAULT NULL,
  `documento_tributario` varchar(100) DEFAULT NULL,
  `forma_pago` varchar(100) DEFAULT NULL,
  `info_forma_pago` varchar(255) DEFAULT NULL,
  `estado_facturacion` varchar(50) DEFAULT NULL,
  `estado_cobro` varchar(50) DEFAULT 'Pendiente',
  `fecha_aviso` datetime DEFAULT NULL,
  `fecha_llegada` datetime DEFAULT NULL,
  `fecha_termino` datetime DEFAULT NULL,
  `fecha_documento` date DEFAULT NULL,
  `fecha_pago` date DEFAULT NULL,
  `nombre_pagador` varchar(255) DEFAULT NULL,
  `num_caso` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_cotizacion` (`id_cotizacion`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `ordenes_trabajo_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`),
  CONSTRAINT `ordenes_trabajo_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: roles
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar rol Administrador por defecto
INSERT INTO `roles` (`id`, `nombre`) VALUES (1, 'Administrador');

-- Tabla: servicios
CREATE TABLE `servicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(255) NOT NULL,
  `id_division` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_division` (`id_division`),
  CONSTRAINT `servicios_ibfk_1` FOREIGN KEY (`id_division`) REFERENCES `division` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: tarifa_especial
CREATE TABLE `tarifa_especial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `precio_base` int(11) DEFAULT 0,
  `valor_km` int(11) DEFAULT 0,
  `tope_km` int(11) DEFAULT 0,
  `valor_custodia` int(11) DEFAULT 0,
  `id_cliente` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `tarifa_especial_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: tipo_camino
CREATE TABLE `tipo_camino` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `monto` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: turno
CREATE TABLE `turno` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `monto` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: usuarios
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `NOMBRES` varchar(255) NOT NULL,
  `APELLIDOS` varchar(255) NOT NULL,
  `TELEFONO` varchar(50) DEFAULT NULL,
  `EMAIL` varchar(255) DEFAULT NULL,
  `NOMBRE_USUARIO` varchar(100) NOT NULL,
  `CONTRASENA` varchar(255) NOT NULL,
  `id_rol` int(11) DEFAULT NULL,
  `FECHA` datetime DEFAULT current_timestamp(),
  `FECHA_MODIFICACION` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `NOMBRE_USUARIO` (`NOMBRE_USUARIO`),
  KEY `id_rol` (`id_rol`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar usuario admin por defecto (contraseña: admin en SHA256)
INSERT INTO `usuarios` (`id`, `NOMBRES`, `APELLIDOS`, `TELEFONO`, `EMAIL`, `NOMBRE_USUARIO`, `CONTRASENA`, `id_rol`, `FECHA`, `FECHA_MODIFICACION`) 
VALUES (1, '', '', NULL, NULL, 'admin', '6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b', 1, '2026-02-21 02:02:00', '2026-02-21 02:03:08');

-- Tabla: vehiculos
CREATE TABLE `vehiculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Tipo` varchar(100) DEFAULT NULL,
  `sub_tipo` varchar(100) DEFAULT NULL,
  `tipo_direccion` varchar(100) DEFAULT NULL,
  `carga` varchar(100) DEFAULT NULL,
  `monto_inicial` decimal(15,2) DEFAULT 0.00,
  `Nombre` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## �📋 Resumen

Este documento describe la estructura completa de la base de datos del **Sistema de Gestión de Cotizaciones y Órdenes de Trabajo**.

---

## 🗂️ Tablas del Sistema

### 1. **asignaciones**
Tabla de asignaciones de empleados, vehículos y asociados a órdenes de trabajo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `id_ot` | int(11) | FK a ordenes_trabajo |
| `id_vehiculo` | int(11) | FK a flota |
| `id_empleado` | int(11) | FK a empleados |
| `id_asociado` | int(11) | FK a asociados |
| `fecha` | date | Fecha de asignación |
| `id_usuario` | int(11) | FK a usuarios (quién asignó) |

**Constraints:**
- `id_ot` → `ordenes_trabajo(id)`
- `id_vehiculo` → `flota(id)`
- `id_empleado` → `empleados(id)`
- `id_asociado` → `asociados(id)`
- `id_usuario` → `usuarios(id)`

---

### 2. **asociados**
Registro de asociados externos (contratistas/terceros).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `NOMBRE` | varchar(255) | Nombre del asociado |
| `RUT` | varchar(20) | RUT del asociado |
| `TELEFONO` | varchar(50) | Teléfono de contacto |
| `EMAIL` | varchar(255) | Correo electrónico |
| `VEHICULO` | varchar(255) | Descripción del vehículo |

---

### 3. **cargos**
Catálogo de cargos para empleados.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `nombre` | varchar(255) | Nombre del cargo |
| `descripcion` | text | Descripción detallada |

---

### 4. **clientes**
Registro de clientes del sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `NOMBRE` | varchar(255) | Nombre del cliente |
| `RUT` | varchar(20) | RUT único del cliente |
| `TIPO` | varchar(50) | Tipo de cliente |
| `DIRECCION` | varchar(500) | Dirección física |
| `TELEFONO` | varchar(50) | Teléfono de contacto |
| `EMAIL` | varchar(255) | Correo electrónico |
| `GIRO` | varchar(255) | Giro del negocio |
| `credito` | decimal(15,2) | Límite de crédito actual (default: 300000) |
| `credito_asignado` | decimal(15,2) | Crédito asignado (default: 300000) |

**Índices:** UNIQUE en `RUT`

---

### 5. **codigos_autorizacion**
Códigos de autorización para validar operaciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `rut_cliente` | varchar(20) | RUT del cliente |
| `codigo` | varchar(50) | Código de autorización |
| `creado_por` | varchar(255) | Usuario que creó el código |
| `creado_en` | datetime | Fecha de creación (default: current_timestamp) |
| `estado` | varchar(20) | Estado: pendiente/aprobado/rechazado (default: 'pendiente') |

---

### 6. **comunas**
Catálogo de comunas con montos asociados para cotizaciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `nombre` | varchar(255) | Nombre de la comuna |
| `region` | varchar(255) | Región a la que pertenece |
| `monto` | decimal(15,2) | Monto asociado (default: 0) |

---

### 7. **costos_variables**
Catálogo de costos variables configurables.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `Tipo` | varchar(50) | Tipo de costo |
| `Nombre` | varchar(255) | Nombre del costo |
| `monto` | decimal(15,2) | Monto del costo |
| `Descripcion` | text | Descripción detallada |
| `grupo` | varchar(100) | Grupo al que pertenece |

---

### 8. **cotizaciones**
Tabla principal de cotizaciones de servicios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `rut_CLIENTE` | varchar(20) | RUT del cliente |
| `id_division` | int(11) | FK a division |
| `id_servicio` | int(11) | FK a servicios |
| `id_turno` | int(11) | FK a turno |
| `id_tipo_vehiculo` | int(11) | FK a vehiculos |
| `id_sub_tipo` | varchar(100) | Sub-tipo de vehículo |
| `id_comuna_origen` | int(11) | FK a comunas (origen) |
| `id_comuna_destino` | int(11) | FK a comunas (destino) |
| `direccion_origen` | varchar(500) | Dirección de origen |
| `direccion_destino` | varchar(500) | Dirección de destino |
| `tipo_camino` | int(11) | FK a tipo_camino |
| `tipo_direccion` | varchar(100) | Tipo de dirección |
| `carga` | varchar(100) | Tipo de carga |
| `total_km` | decimal(10,2) | Kilómetros totales |
| `monto_inicial` | decimal(15,2) | Monto inicial estimado |
| `monto_final` | decimal(15,2) | Monto final de la cotización |
| `monto_turno` | decimal(15,2) | Monto adicional por turno |
| `monto_tipo_camino` | decimal(15,2) | Monto adicional por tipo de camino |
| `credito_aplicado` | decimal(15,2) | Crédito aplicado a la cotización |
| `Descripcion` | text | Descripción del servicio |
| `Panne` | varchar(255) | Campo Panne |
| `base_salida` | varchar(255) | Base de salida |
| `nombre_solicitante` | varchar(255) | Nombre de quien solicita |
| `email_CLIENTE` | varchar(255) | Email del cliente |
| `telefono_CLIENTE` | varchar(50) | Teléfono del cliente |
| `coordinador_id` | int(11) | FK a usuarios (coordinador) |
| `forma_de_pago` | varchar(100) | Forma de pago |
| `documento_tributario` | varchar(100) | Tipo de documento tributario |
| `estado` | varchar(50) | Estado: Pendiente/Aprobada/Rechazada (default: 'Pendiente') |
| `Fecha_servicio` | date | Fecha programada del servicio |
| `fecha_creacion` | datetime | Fecha de creación (default: current_timestamp) |
| `token_aprobacion` | varchar(255) | Token para aprobación vía email |

**Constraints:**
- `id_division` → `division(id)`
- `id_servicio` → `servicios(id)`
- `id_turno` → `turno(id)`
- `id_tipo_vehiculo` → `vehiculos(id)`
- `id_comuna_origen` → `comunas(id)`
- `id_comuna_destino` → `comunas(id)`
- `coordinador_id` → `usuarios(id)`

**Índices:** `id_division`, `id_servicio`, `id_turno`, `id_tipo_vehiculo`, `id_comuna_origen`, `id_comuna_destino`, `coordinador_id`

---

### 9. **division**
Divisiones de servicios ofrecidos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `NOMBRE` | varchar(255) | Nombre de la división |

---

### 10. **empleados**
Registro de empleados de la empresa.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `rut` | varchar(20) | RUT del empleado |
| `nombre_completo` | varchar(255) | Nombre completo |
| `celular` | varchar(50) | Teléfono celular |
| `email` | varchar(255) | Correo electrónico |
| `id_cargo` | int(11) | FK a cargos |
| `Banco` | varchar(100) | Banco para depósito |
| `tipo_cuenta` | varchar(100) | Tipo de cuenta bancaria |
| `numero_cuenta` | varchar(100) | Número de cuenta |
| `centro_costo` | varchar(100) | Centro de costo asignado |

**Constraints:** `id_cargo` → `cargos(id)`

**Índices:** `id_cargo`

---

### 11. **flota**
Registro de vehículos de la flota propia.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `patente` | varchar(20) | Patente del vehículo |
| `marca` | varchar(100) | Marca |
| `modelo` | varchar(100) | Modelo |
| `codigo_movil` | varchar(50) | Código interno del móvil |

---

### 12. **gastos**
Registro de gastos asociados a órdenes de trabajo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `id_ot` | int(11) | FK a ordenes_trabajo |
| `id_empleado` | int(11) | FK a empleados (si aplica) |
| `id_asociado` | int(11) | FK a asociados (si aplica) |
| `tipo` | varchar(100) | Tipo de gasto |
| `monto` | decimal(15,2) | Monto del gasto |
| `descripcion` | text | Descripción del gasto |
| `fecha` | date | Fecha del gasto |
| `id_usuario` | int(11) | FK a usuarios (quién registró) |

**Constraints:**
- `id_ot` → `ordenes_trabajo(id)`
- `id_empleado` → `empleados(id)`
- `id_asociado` → `asociados(id)`
- `id_usuario` → `usuarios(id)`

**Índices:** `id_ot`, `id_empleado`, `id_asociado`, `id_usuario`

---

### 13. **observaciones**
Observaciones y comentarios en órdenes de trabajo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `id_ot` | int(11) | FK a ordenes_trabajo |
| `comentario` | text | Texto de la observación |
| `fecha_creacion` | datetime | Fecha de creación (default: current_timestamp) |
| `fecha_modificacion` | datetime | Fecha de modificación (auto update) |
| `id_usuario` | int(11) | FK a usuarios (quién comentó) |

**Constraints:**
- `id_ot` → `ordenes_trabajo(id)`
- `id_usuario` → `usuarios(id)`

**Índices:** `id_ot`, `id_usuario`

---

### 14. **ordenes_trabajo**
Órdenes de trabajo generadas desde cotizaciones o directamente.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `id_cotizacion` | int(11) | FK a cotizaciones (opcional) |
| `Descripcion` | text | Descripción de la OT |
| `Fecha_creacion` | datetime | Fecha de creación (default: current_timestamp) |
| `Fecha_Modificacion` | datetime | Fecha de modificación (auto update) |
| `id_usuario` | int(11) | FK a usuarios (creador) |
| `estado` | varchar(50) | Estado de la OT (default: 'En programación') |
| `info_marca` | varchar(255) | Marca del vehículo a transportar |
| `info_modelo` | varchar(255) | Modelo del vehículo |
| `info_patente` | varchar(100) | Patente del vehículo |
| `info_chofer` | varchar(255) | Nombre del chofer |
| `info_telefono` | varchar(50) | Teléfono de contacto |
| `info_contacto` | varchar(255) | Nombre de contacto |
| `documento_tributario` | varchar(100) | Tipo de documento tributario |
| `forma_pago` | varchar(100) | Forma de pago |
| `info_forma_pago` | varchar(255) | Información adicional de pago |
| `estado_facturacion` | varchar(50) | Estado de facturación |
| `estado_cobro` | varchar(50) | Estado de cobro (default: 'Pendiente') |
| `fecha_aviso` | datetime | Fecha de aviso al cliente |
| `fecha_llegada` | datetime | Fecha de llegada del servicio |
| `fecha_termino` | datetime | Fecha de término del servicio |
| `fecha_documento` | date | Fecha del documento tributario |
| `fecha_pago` | date | Fecha de pago recibido |
| `nombre_pagador` | varchar(255) | Nombre de quien pagó |
| `num_caso` | varchar(100) | Número de caso interno |

**Constraints:**
- `id_cotizacion` → `cotizaciones(id)`
- `id_usuario` → `usuarios(id)`

**Índices:** `id_cotizacion`, `id_usuario`

---

### 15. **roles**
Roles de usuario para control de acceso.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `nombre` | varchar(100) | Nombre del rol |

**Datos por defecto:** `(1, 'Administrador')`

---

### 16. **servicios**
Catálogo de servicios ofrecidos por división.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `Nombre` | varchar(255) | Nombre del servicio |
| `id_division` | int(11) | FK a division |

**Constraints:** `id_division` → `division(id)`

**Índices:** `id_division`

---

### 17. **tarifa_especial**
Tarifas especiales configuradas por cliente.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `precio_base` | int(11) | Precio base (default: 0) |
| `valor_km` | int(11) | Valor por kilómetro (default: 0) |
| `tope_km` | int(11) | Tope máximo de km (default: 0) |
| `valor_custodia` | int(11) | Valor de custodia (default: 0) |
| `id_cliente` | int(11) | FK a clientes |

**Constraints:** `id_cliente` → `clientes(id)`

**Índices:** `id_cliente`

---

### 18. **tipo_camino**
Tipos de camino/recorrido con montos adicionales.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `nombre` | varchar(255) | Nombre del tipo de camino |
| `monto` | decimal(15,2) | Monto adicional (default: 0) |

---

### 19. **turno**
Turnos de servicio con montos adicionales.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `nombre` | varchar(255) | Nombre del turno |
| `monto` | decimal(15,2) | Monto adicional (default: 0) |

---

### 20. **usuarios**
Usuarios del sistema con autenticación.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `NOMBRES` | varchar(255) | Nombres del usuario |
| `APELLIDOS` | varchar(255) | Apellidos del usuario |
| `TELEFONO` | varchar(50) | Teléfono de contacto |
| `EMAIL` | varchar(255) | Correo electrónico |
| `NOMBRE_USUARIO` | varchar(100) | Nombre de usuario único (login) |
| `CONTRASENA` | varchar(255) | Contraseña en SHA256 |
| `id_rol` | int(11) | FK a roles |
| `FECHA` | datetime | Fecha de creación (default: current_timestamp) |
| `FECHA_MODIFICACION` | datetime | Fecha de modificación (auto update) |

**Constraints:** `id_rol` → `roles(id)`

**Índices:** UNIQUE en `NOMBRE_USUARIO`, `id_rol`

**Datos por defecto:**
```sql
(1, '', '', NULL, NULL, 'admin', '6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b', 1, '2026-02-21 02:02:00', '2026-02-21 02:03:08')
```
> **Nota:** La contraseña es el hash SHA256 de "admin"

---

### 21. **vehiculos**
Tipos de vehículos disponibles para cotización.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(11) | ID primario autoincremental |
| `Tipo` | varchar(100) | Categoría del vehículo |
| `sub_tipo` | varchar(100) | Sub-categoría |
| `tipo_direccion` | varchar(100) | Tipo de dirección |
| `carga` | varchar(100) | Capacidad de carga |
| `monto_inicial` | decimal(15,2) | Monto base del vehículo (default: 0) |
| `Nombre` | varchar(255) | Nombre descriptivo |

---

## 🔗 Diagrama de Relaciones

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   division      │     │    servicios     │     │     turno       │
├─────────────────┤     ├──────────────────┤     ├─────────────────┤
│ id (PK)         │◄────┤ id_division (FK) │     │ id (PK)         │
│ NOMBRE          │     │ id (PK)          │     │ nombre          │
└─────────────────┘     │ Nombre           │     │ monto           │
                        └──────────────────┘     └─────────────────┘
                                │
                                │
                                ▼
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│    clientes     │◄────┤  cotizaciones    │────►│    comunas      │
├─────────────────┤     ├──────────────────┤     ├─────────────────┤
│ id (PK)         │     │ id (PK)          │     │ id (PK)         │
│ NOMBRE          │     │ rut_CLIENTE      │     │ nombre          │
│ RUT             │     │ id_division (FK) │     │ region          │
│ credito         │     │ id_servicio (FK) │     │ monto           │
└─────────────────┘     │ id_turno (FK)    │     └─────────────────┘
        │               │ id_tipo_vehiculo │◄──────────┐
        │               │ id_comuna_origen │           │
        │               │ id_comuna_destino │           │
        │               │ estado           │           │
        │               │ monto_final      │           │
        │               └──────────────────┘           │
        │                        │                       │
        │                        │                       │
        │                        ▼                       │
        │               ┌──────────────────┐           │
        │               │  ordenes_trabajo │           │
        │               ├──────────────────┤           │
        │               │ id (PK)          │           │
        └──────────────►│ id_cotizacion(FK)│           │
                        │ estado           │           │
                        │ info_patente     │           │
                        └──────────────────┘           │
                                 │                     │
                                 │                     │
            ┌────────────────────┼─────────────────────┘
            │                    │
            ▼                    ▼                    ▼
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│   asignaciones   │  │   observaciones    │  │      gastos      │
├──────────────────┤  ├──────────────────┤  ├──────────────────┤
│ id (PK)          │  │ id (PK)            │  │ id (PK)          │
│ id_ot (FK)       │  │ id_ot (FK)         │  │ id_ot (FK)       │
│ id_vehiculo (FK) │  │ comentario         │  │ tipo             │
│ id_empleado (FK) │  │ id_usuario (FK)    │  │ monto            │
│ id_asociado (FK) │  └──────────────────┘  │ id_usuario (FK)  │
└──────────────────┘                        └──────────────────┘
         │
         │         ┌──────────────────┐
         │         │      flota       │
         │         ├──────────────────┤
         └────────►│ id (PK)          │
                   │ patente          │
                   │ marca            │
                   │ modelo           │
                   └──────────────────┘

┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│    usuarios      │  │     roles        │  │    cargos        │
├──────────────────┤  ├──────────────────┤  ├──────────────────┤
│ id (PK)          │  │ id (PK)          │  │ id (PK)          │
│ NOMBRE_USUARIO   │──┤ id_rol (FK)      │  │ nombre           │
│ CONTRASENA (SHA) │  │ nombre           │  └──────────────────┘
│ id_rol (FK)      │  └──────────────────┘         │
└──────────────────┘                              │
                                                  │
                                                  ▼
                                          ┌──────────────────┐
                                          │    empleados     │
                                          ├──────────────────┤
                                          │ id (PK)          │
                                          │ rut              │
                                          │ nombre_completo  │
                                          │ id_cargo (FK)    │
                                          │ Banco            │
                                          │ numero_cuenta    │
                                          └──────────────────┘

┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│  tarifa_especial │  │  tipo_camino     │  │    asociados     │
├──────────────────┤  ├──────────────────┤  ├──────────────────┤
│ id (PK)          │  │ id (PK)          │  │ id (PK)          │
│ precio_base      │  │ nombre           │  │ NOMBRE           │
│ valor_km         │  │ monto            │  │ RUT              │
│ tope_km          │  └──────────────────┘  │ VEHICULO         │
│ id_cliente (FK)  │                          └──────────────────┘
└──────────────────┘

        ┌──────────────────┐
        │    vehiculos     │
        ├──────────────────┤
        │ id (PK)          │
        │ Tipo             │
        │ sub_tipo         │
        │ monto_inicial    │
        │ Nombre           │
        └──────────────────┘
```

---

## 📝 Notas Importantes

1. **Autenticación:** Las contraseñas se almacenan usando hash **SHA256** en la tabla `usuarios`

2. **Auditoría:** Las tablas principales incluyen campos de fecha de creación y modificación automáticos

3. **Relaciones N:M:** La tabla `asignaciones` maneja la relación muchos-a-muchos entre órdenes de trabajo, empleados, vehículos y asociados

4. **Cálculo de Cotizaciones:** El monto final se compone de:
   - `monto_inicial` (base)
   - `monto_turno` (adicional por turno)
   - `monto_tipo_camino` (adicional por tipo de camino)
   - Menos `credito_aplicado` (si aplica)

5. **Estados:**
   - **Cotizaciones:** Pendiente → Aprobada/Rechazada
   - **Órdenes de Trabajo:** En programación → En ejecución → Completada/Cancelada

6. **Campos de Información en OT:** La tabla `ordenes_trabajo` incluye campos `info_*` para almacenar datos del vehículo/cliente a transportar (diferente de la flota asignada)

---

## 🔧 Configuración Recomendada

```sql
-- Crear base de datos
CREATE DATABASE sistema_cotizaciones CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Usuario recomendado
CREATE USER 'sistema_user'@'localhost' IDENTIFIED BY 'password_seguro';
GRANT ALL PRIVILEGES ON sistema_cotizaciones.* TO 'sistema_user'@'localhost';
FLUSH PRIVILEGES;
```

---

<p align="center">
  <strong>Documentación generada para el Sistema de Cotizaciones</strong><br>
  <em>Basado en la estructura real de la base de datos</em>
</p>
