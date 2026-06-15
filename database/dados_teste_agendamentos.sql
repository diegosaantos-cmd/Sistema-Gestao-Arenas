-- =============================================================================
-- Dados de teste: 5 clientes + 10 agendamentos (bookings)
-- Para visualizar a seção "Próximos Agendamentos" do owner dashboard.
--
-- PRÉ-REQUISITO: já existir ao menos UMA arena com UMA quadra (courts),
-- criada pelo formulário. Os agendamentos são vinculados às quadras existentes.
--
-- Como rodar:  importe este arquivo no banco (phpMyAdmin > Importar, ou
--   mysql -u root arena_sports < database/dados_teste_agendamentos.sql)
--
-- A senha de todos os clientes é "password" (hash bcrypt abaixo).
-- =============================================================================

START TRANSACTION;

-- 1) Usuários do tipo cliente -------------------------------------------------
INSERT INTO users (name, email, password_hash, type, active, created_at, updated_at) VALUES
('Ana Souza',     'ana@teste.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 1, NOW(), NOW()),
('Bruno Lima',    'bruno@teste.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 1, NOW(), NOW()),
('Carla Mendes',  'carla@teste.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 1, NOW(), NOW()),
('Diego Alves',   'diego@teste.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 1, NOW(), NOW()),
('Eduarda Rocha', 'eduarda@teste.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 1, NOW(), NOW());

-- 2) Registros em clients (ligados aos users pelo e-mail) ----------------------
INSERT INTO clients (user_id, date_of_birth, created_at)
SELECT id, '1995-06-15', NOW()
FROM users
WHERE email IN ('ana@teste.com','bruno@teste.com','carla@teste.com','diego@teste.com','eduarda@teste.com');

-- 3) Variáveis: quadras existentes e ids dos clientes -------------------------
-- IMPORTANTE: usamos quadras da MESMA arena, senão os agendamentos ficam
-- divididos entre owners diferentes e cada um vê só uma parte.
SET @arena1 := (SELECT arena_id FROM courts ORDER BY id LIMIT 1);
SET @court1 := (SELECT id FROM courts WHERE arena_id = @arena1 ORDER BY id LIMIT 1);
-- Segunda quadra DA MESMA arena, se houver; senão usa a primeira.
SET @court2 := COALESCE(
    (SELECT id FROM courts WHERE arena_id = @arena1 ORDER BY id LIMIT 1 OFFSET 1),
    @court1
);

SET @cli1 := (SELECT c.id FROM clients c JOIN users u ON u.id = c.user_id WHERE u.email = 'ana@teste.com');
SET @cli2 := (SELECT c.id FROM clients c JOIN users u ON u.id = c.user_id WHERE u.email = 'bruno@teste.com');
SET @cli3 := (SELECT c.id FROM clients c JOIN users u ON u.id = c.user_id WHERE u.email = 'carla@teste.com');
SET @cli4 := (SELECT c.id FROM clients c JOIN users u ON u.id = c.user_id WHERE u.email = 'diego@teste.com');
SET @cli5 := (SELECT c.id FROM clients c JOIN users u ON u.id = c.user_id WHERE u.email = 'eduarda@teste.com');

-- 4) 10 agendamentos futuros (a partir de hoje), nenhum cancelado ------------
INSERT INTO bookings (court_id, client_id, date, start_time, end_time, total_amount, status, created_at, updated_at) VALUES
(@court1, @cli1, CURDATE(),                      '08:00:00', '09:00:00',  80.00, 'confirmed', NOW(), NOW()),
(@court2, @cli1, CURDATE() + INTERVAL 1 DAY,     '10:00:00', '11:00:00',  90.00, 'pending',   NOW(), NOW()),
(@court1, @cli2, CURDATE(),                      '09:00:00', '10:00:00',  80.00, 'confirmed', NOW(), NOW()),
(@court2, @cli2, CURDATE() + INTERVAL 2 DAY,     '18:00:00', '19:00:00', 100.00, 'confirmed', NOW(), NOW()),
(@court1, @cli3, CURDATE() + INTERVAL 1 DAY,     '19:00:00', '20:00:00', 120.00, 'confirmed', NOW(), NOW()),
(@court2, @cli3, CURDATE() + INTERVAL 3 DAY,     '20:00:00', '21:00:00', 120.00, 'pending',   NOW(), NOW()),
(@court1, @cli4, CURDATE() + INTERVAL 2 DAY,     '07:00:00', '08:00:00',  70.00, 'confirmed', NOW(), NOW()),
(@court2, @cli4, CURDATE() + INTERVAL 4 DAY,     '21:00:00', '22:00:00', 110.00, 'confirmed', NOW(), NOW()),
(@court1, @cli5, CURDATE() + INTERVAL 3 DAY,     '16:00:00', '17:00:00',  95.00, 'pending',   NOW(), NOW()),
(@court2, @cli5, CURDATE() + INTERVAL 5 DAY,     '17:00:00', '18:00:00',  95.00, 'confirmed', NOW(), NOW());

COMMIT;
