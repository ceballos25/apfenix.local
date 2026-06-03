-- ============================================================
-- Migración: Rol VENDEDOR + Metas + Índice ventas/vendedor
-- Base de datos: apfenixc_ap_fenix
-- Ejecutar ANTES de desplegar el código PHP
-- ============================================================

-- 1. Campos nuevos en admins (vendedores y administradores)
ALTER TABLE `admins`
  ADD COLUMN `name_admin` VARCHAR(100) DEFAULT NULL AFTER `email_admin`,
  ADD COLUMN `goal_type_admin` ENUM('ventas', 'numeros') NOT NULL DEFAULT 'ventas' COMMENT 'Tipo de meta diaria' AFTER `status_admin`,
  ADD COLUMN `goal_value_admin` INT(11) NOT NULL DEFAULT 0 COMMENT 'Valor meta diaria' AFTER `goal_type_admin`;

-- 2. Normalizar roles existentes (NULL → administrador)
UPDATE `admins`
SET `rol_admin` = 'administrador'
WHERE `rol_admin` IS NULL OR TRIM(`rol_admin`) = '';

-- 3. Índice en ventas por vendedor (consultas diarias optimizadas)
ALTER TABLE `sales`
  ADD KEY `idx_sales_admin` (`id_admin_sale`);

-- 4. Llave foránea opcional (integridad referencial)
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_admins`
    FOREIGN KEY (`id_admin_sale`) REFERENCES `admins` (`id_admin`)
    ON DELETE SET NULL ON UPDATE CASCADE;
