-- ============================================================
-- Migración: campo user_ns_agent en agents
-- Base de datos: apfenixc_ap_fenix
-- Ejecutar ANTES de desplegar el código PHP
-- ============================================================

ALTER TABLE `agents`
  ADD COLUMN `user_ns_agent` VARCHAR(100) DEFAULT NULL
    COMMENT 'Identificador de usuario en el namespace del Agente IA'
    AFTER `code_agent`;
