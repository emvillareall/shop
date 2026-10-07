SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TEMPORARY TABLE tmp_gym_categories (nombre_categoria VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL PRIMARY KEY) ENGINE=InnoDB;
INSERT INTO tmp_gym_categories (nombre_categoria) VALUES
('SHORTS'),
('TOPS / SPORTS BRA'),
('T-SHIRTS'),
('HOODIES'),
('ACCESORIOS / MEDIAS'),
('SWEATSHIRTS / CREW'),
('LEGGINGS'),
('TOPS / TANK'),
('ENTERIZOS / ALL-IN-ONE'),
('JOGGERS / PANTS'),
('PANTS'),
('ACCESORIOS / BOLSOS'),
('ACCESORIOS / HEADBANDS'),
('ACCESORIOS / STRAPS'),
('ACCESORIOS / VISERAS'),
('ACCESORIOS / BELT'),
('ACCESORIOS / BOTELLAS'),
('SWEATSHIRTS / PULLOVER'),
('TOPS / CROP');
CREATE TEMPORARY TABLE tmp_gym_colors (nombre_color VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL PRIMARY KEY, codigo_color VARCHAR(255) NOT NULL) ENGINE=InnoDB;
INSERT INTO tmp_gym_colors (nombre_color,codigo_color) VALUES
('Black','#070707'),
('White','#F8F8F8'),
('Charcoal Grey Marl','#36454F'),
('Heavy Blue','#1F4E79'),
('Butter Yellow','#ECDEA4'),
('White/Light Grey Core Marl/Black','#F1F1F1'),
('Ecru Brown/Acid Wash Small Ball','#D8C3A5'),
('Classic Blue','#2563EB'),
('Success Blue','#224E76'),
('Weighted Purple/Metal Purple','#7634AF'),
('Celeste Blue/Marl','#8ACEE8'),
('Indigo Purple','#870787'),
('Electric Pink','#FF1490'),
('Iron Blue','#466B8A'),
('Classic Green','#2E8B57'),
('Asphalt Grey/Black','#575757'),
('Plum Brown','#A97B58'),
('Darkest Teal/Functional Teal','#014D4E'),
('Carmine Red/Wash','#960018'),
('Bold Orange','#FF8C00'),
('Energy Gelato Green','#318B54'),
('Winning Pink/Sour Pink','#FF69B4'),
('Darkest Teal','#085455'),
('Physical Pink','#FFC0CB'),
('Heritage Brown','#AC7B5B'),
('Moss Olive/Core Olive','#838000'),
('Asphalt Grey/Black/Spray Dye','#5A5754'),
('Dusk Purple/Wash','#7114B4'),
('Steady Purple','#8E0E8E'),
('Black/Asphalt Grey','#0E0E0E'),
('Electric Pink/Sorbet Yellow','#FF2093'),
('Cherry Purple/Reset Pink','#8E4585'),
('Tinted White/Cherry Purple','#F2F2F0'),
('Black/Tinted White','#151515'),
('Super-Set Pink','#FF5A8A'),
('Force Blue/Steady Purple','#4169E1'),
('Focus Pink/Tinted White','#F4A6C7'),
('Black/Smokey Grey','#232323'),
('Cherry Purple/Wash','#914582'),
('Strength Pink','#F8B9C4'),
('Carmine Red','#9D071F'),
('Chilled Blue','#60A5FA'),
('Reset Pink','#FF7F8A'),
('Stone Beige','#DBC3A2'),
('Discipline Green','#348B57'),
('Super-Set Pink/Focus Pink','#FF5A87'),
('Black/Focus Pink','#2A2A2A'),
('Conditioning Red','#C62828'),
('Force Blue','#4469DE'),
('Calm Pink','#F1B2BD'),
('Tinted White','#EBEBE9'),
('Soft White','#EAEAEA'),
('Pebble Grey','#83807D'),
('Black/Conditioning Red','#313131'),
('Athletic Red','#F11C1C'),
('White/Black','#E3E3E3'),
('Poise Pink','#F48FB1'),
('Track Green','#378B57'),
('Rich Maroon/Marl','#800000'),
('Black Marl','#3F3F3F');
CREATE TEMPORARY TABLE tmp_gym_products (orden INT NOT NULL PRIMARY KEY, codigo_producto VARCHAR(20) NOT NULL UNIQUE, descripcion_producto VARCHAR(255) NOT NULL, categoria VARCHAR(255) NOT NULL, cantidad_compra_producto INT NOT NULL, costo_prorrateado DECIMAL(12,2) NOT NULL) ENGINE=InnoDB;
INSERT INTO tmp_gym_products (orden,codigo_producto,descripcion_producto,categoria,cantidad_compra_producto,costo_prorrateado) VALUES
(1,'GYM-0001','Lifting Essentials Graphic Brushed 4'' Shorts','SHORTS',2,12.66),
(2,'GYM-0002','Asymmetric Sports Bra','TOPS / SPORTS BRA',2,11.91),
(3,'GYM-0003','FLEX SHORTS','SHORTS',1,13.03),
(4,'GYM-0004','Varsity Graphic Oversized T-shirt','T-SHIRTS',1,14.89),
(5,'GYM-0005','Strength Department Oversized Hoodie','HOODIES',1,26.06),
(6,'GYM-0006','Legacy Cotton Shorts','SHORTS',2,16.38),
(7,'GYM-0007','Everyday Seamless Shorts','SHORTS',14,34.75),
(8,'GYM-0008','Everyday Seamless Racerback Sports Bra','TOPS / SPORTS BRA',1,32.27),
(9,'GYM-0009','Crew Socks 6pk','ACCESORIOS / MEDIAS',2,24.57),
(10,'GYM-0010','Power Washed Crew','SWEATSHIRTS / CREW',3,20.85),
(11,'GYM-0011','Mesh Oversized T-Shirt','T-SHIRTS',1,28.55),
(12,'GYM-0012','Legacy Bra','TOPS / SPORTS BRA',1,11.17),
(13,'GYM-0013','Legacy Logo Leggings','LEGGINGS',1,20.11),
(14,'GYM-0014','Legacy Tall Leggings','LEGGINGS',1,20.11),
(15,'GYM-0015','Adapt Fleck Seamless Leggings (Costo USD 18.00)','LEGGINGS',2,22.34),
(16,'GYM-0016','Vital Regular Leggings','LEGGINGS',2,20.11),
(17,'GYM-0017','Legacy Logo Tight Shorts','SHORTS',1,14.89),
(18,'GYM-0018','Contour Stitch Tank with Shelf','TOPS / TANK',2,14.15),
(19,'GYM-0019','Woven Shorts','SHORTS',4,16.88),
(20,'GYM-0020','Lifting Dipped Waistband All-In-One','ENTERIZOS / ALL-IN-ONE',3,23.83),
(21,'GYM-0021','Whitney Regular Legging','LEGGINGS',1,18.62),
(22,'GYM-0022','Adapt Camo Seamless Leggings','LEGGINGS',2,22.34),
(23,'GYM-0023','Crossover Leggings','LEGGINGS',1,18.62),
(24,'GYM-0024','Adapt Fleck Seamless Shorts (Costo USD 13.20)','SHORTS',1,16.38),
(25,'GYM-0025','Gains Seamless Washed Short','SHORTS',1,14.89),
(26,'GYM-0026','Lift Seamless High-Rise Leggings','LEGGINGS',1,22.34),
(27,'GYM-0027','Halter Neck Cami with Shelf','TOPS / TANK',1,14.15),
(28,'GYM-0028','Lifting Pocket Shorts','SHORTS',1,15.64),
(29,'GYM-0029','Lifting Club Mesh 5" Shorts','SHORTS',1,14.89),
(30,'GYM-0030','Fleece Shorts','SHORTS',2,20.11),
(31,'GYM-0031','Gains Washed Heavyweight Cotton T-Shirt','T-SHIRTS',1,14.89),
(32,'GYM-0032','Locked In Graphic Jogger','JOGGERS / PANTS',1,38.23),
(33,'GYM-0033','Everyday Seamless Shelf Tank','TOPS / TANK',2,37.23),
(34,'GYM-0034','Power Washed Joggers (Costo USD 22.40)','JOGGERS / PANTS',6,27.80),
(35,'GYM-0035','Shelf Cami Tank','TOPS / TANK',2,23.58),
(36,'GYM-0036','Adapt Animal Leggings','LEGGINGS',8,29.79),
(37,'GYM-0037','To From Pants','PANTS',5,27.80),
(38,'GYM-0038','Relay Seamless Midi Tank','TOPS / TANK',4,28.55),
(39,'GYM-0039','Lightweight Tricot Wide Leg Pants','PANTS',8,29.79),
(40,'GYM-0040','Halter Bralette','TOPS / SPORTS BRA',4,27.30),
(41,'GYM-0041','Everyday Seamless Ribbed Tank','TOPS / TANK',5,17.38),
(42,'GYM-0042','Adapt Fleck Seamless Shorts (Costo USD 17.60)','SHORTS',9,21.84),
(43,'GYM-0043','Light Support Minimal Halterneck Bra','TOPS / SPORTS BRA',4,18.62),
(44,'GYM-0044','Power Washed Joggers (Costo USD 28.00)','JOGGERS / PANTS',6,34.75),
(45,'GYM-0045','Adapt Fleck Seamless Leggings (Costo USD 24.00)','LEGGINGS',8,29.79),
(46,'GYM-0046','Everyday Holdall Small','ACCESORIOS / BOLSOS',4,32.77),
(47,'GYM-0047','Diffuse Logo Headband (Costo USD 12.00)','ACCESORIOS / HEADBANDS',2,14.89),
(48,'GYM-0048','Diffuse Logo Headband (Costo USD 10.00)','ACCESORIOS / HEADBANDS',2,12.41),
(49,'GYM-0049','Comfy Rest Day Socks (Costo USD 4.20)','ACCESORIOS / MEDIAS',4,5.21),
(50,'GYM-0050','Cut Out Bandeau','TOPS / SPORTS BRA',2,11.17),
(51,'GYM-0051','Silicone Lifting Straps','ACCESORIOS / STRAPS',2,12.41),
(52,'GYM-0052','Visor','ACCESORIOS / VISERAS',2,18.62),
(53,'GYM-0053','Adapt Animal Shorts','SHORTS',3,21.84),
(54,'GYM-0054','Medium Support Running Sports Bra (Costo USD 22.00)','TOPS / SPORTS BRA',2,27.30),
(55,'GYM-0055','Velcro Weightlifting Belt','ACCESORIOS / BELT',2,27.30),
(56,'GYM-0056','Adapt Fleck Halterneck Sports Bra','TOPS / SPORTS BRA',1,22.84),
(57,'GYM-0057','Power T-Shirt','T-SHIRTS',4,22.34),
(58,'GYM-0058','Accent Conditioning Shorts','SHORTS',18,22.34),
(59,'GYM-0059','Diffuse Sweat Headband','ACCESORIOS / HEADBANDS',2,12.41),
(60,'GYM-0060','Lucky Graphic Silicone Lifting Straps','ACCESORIOS / STRAPS',2,16.38),
(61,'GYM-0061','1L Water Bottle','ACCESORIOS / BOTELLAS',3,6.95),
(62,'GYM-0062','Comfy Rest Day Socks (Costo USD 7.00)','ACCESORIOS / MEDIAS',6,8.69),
(63,'GYM-0063','Lifting Mesh 5" Shorts','SHORTS',4,24.82),
(64,'GYM-0064','Adapt Animal Tank Top with Shelf','TOPS / TANK',5,23.58),
(65,'GYM-0065','Adapt Animal Sports Bra','TOPS / SPORTS BRA',3,27.30),
(66,'GYM-0066','Minimal Sports Bra','TOPS / SPORTS BRA',1,18.62),
(67,'GYM-0067','Cotton Blend Racerback Sports Bra','TOPS / SPORTS BRA',1,18.62),
(68,'GYM-0068','Apres Lift Graphic Crew Sock Single','ACCESORIOS / MEDIAS',2,9.93),
(69,'GYM-0069','Running Graphic Crew Socks 2pk','ACCESORIOS / MEDIAS',2,14.89),
(70,'GYM-0070','Minimal Sports Bra 2.0','TOPS / SPORTS BRA',2,29.79),
(71,'GYM-0071','Training Shelf Cami Tank','TOPS / TANK',2,27.80),
(72,'GYM-0072','Vital Seamless Midi Zip 2.0 Pullover','SWEATSHIRTS / PULLOVER',4,27.80),
(73,'GYM-0073','Vital Crop Top','TOPS / CROP',2,14.15),
(74,'GYM-0074','Ruched Sports Bra','TOPS / SPORTS BRA',4,14.89),
(75,'GYM-0075','Medium Support Running Sports Bra (Costo USD 26.40)','TOPS / SPORTS BRA',4,32.77);
CREATE TEMPORARY TABLE tmp_gym_variants (orden INT NOT NULL PRIMARY KEY, codigo_producto VARCHAR(20) NOT NULL, nombre_color VARCHAR(255) NOT NULL, talla VARCHAR(50) NOT NULL, cantidad INT NOT NULL, costo_unitario_dolares DECIMAL(12,2) NOT NULL, subtotal_dolares DECIMAL(12,2) NOT NULL) ENGINE=InnoDB;
INSERT INTO tmp_gym_variants (orden,codigo_producto,nombre_color,talla,cantidad,costo_unitario_dolares,subtotal_dolares) VALUES
(1,'GYM-0001','Black','S',1,12.66,12.66),
(2,'GYM-0002','Black','S',1,11.91,11.91),
(3,'GYM-0002','White','S',1,11.91,11.91),
(4,'GYM-0003','Charcoal Grey Marl','S',1,13.03,13.03),
(5,'GYM-0004','Heavy Blue','XS-S',1,14.89,14.89),
(6,'GYM-0005','Black','XS-S',1,26.06,26.06),
(7,'GYM-0006','Black','S',1,16.38,16.38),
(8,'GYM-0007','Butter Yellow','S',3,34.75,104.25),
(9,'GYM-0007','Butter Yellow','M',3,34.75,104.25),
(10,'GYM-0008','Butter Yellow','M',1,32.27,32.27),
(11,'GYM-0009','White/Light Grey Core Marl/Black','M',2,24.57,49.14),
(12,'GYM-0010','Ecru Brown/Acid Wash Small Ball','XS-S',1,20.85,20.85),
(13,'GYM-0010','Ecru Brown/Acid Wash Small Ball','S',1,20.85,20.85),
(14,'GYM-0010','Ecru Brown/Acid Wash Small Ball','M',1,20.85,20.85),
(15,'GYM-0011','Black','M',1,28.55,28.55),
(16,'GYM-0012','Classic Blue','S',1,11.17,11.17),
(17,'GYM-0013','Success Blue','S',1,20.11,20.11),
(18,'GYM-0014','Black','S',1,20.11,20.11),
(19,'GYM-0015','Weighted Purple/Metal Purple','S',1,22.34,22.34),
(20,'GYM-0016','Celeste Blue/Marl','S',1,20.11,20.11),
(21,'GYM-0016','Celeste Blue/Marl','M',1,20.11,20.11),
(22,'GYM-0017','Success Blue','S',1,14.89,14.89),
(23,'GYM-0018','Heavy Blue','S',1,14.15,14.15),
(24,'GYM-0018','Heavy Blue','M',1,14.15,14.15),
(25,'GYM-0019','Indigo Purple','S',1,16.88,16.88),
(26,'GYM-0019','Indigo Purple','M',1,16.88,16.88),
(27,'GYM-0019','Electric Pink','S',1,16.88,16.88),
(28,'GYM-0019','Electric Pink','M',1,16.88,16.88),
(29,'GYM-0020','Iron Blue','XS-S',1,23.83,23.83),
(30,'GYM-0020','Iron Blue','S',1,23.83,23.83),
(31,'GYM-0020','Iron Blue','M',1,23.83,23.83),
(32,'GYM-0021','Classic Green','S',1,18.62,18.62),
(33,'GYM-0022','Asphalt Grey/Black','XS-S',1,22.34,22.34),
(34,'GYM-0023','Plum Brown','M',1,18.62,18.62),
(35,'GYM-0024','Darkest Teal/Functional Teal','XS-S',1,16.38,16.38),
(36,'GYM-0025','Carmine Red/Wash','XS-S',1,14.89,14.89),
(37,'GYM-0026','Bold Orange','XS-S',1,22.34,22.34),
(38,'GYM-0027','Energy Gelato Green','XXL',1,14.15,14.15),
(39,'GYM-0015','Winning Pink/Sour Pink','XS-S',1,22.34,22.34),
(40,'GYM-0028','Darkest Teal','XS-S',1,15.64,15.64),
(41,'GYM-0029','Physical Pink','XS-S',1,14.89,14.89),
(42,'GYM-0006','Black','XS-S',1,16.38,16.38),
(43,'GYM-0001','Black','XS-S',1,12.66,12.66),
(44,'GYM-0030','Heritage Brown','S',1,20.11,20.11),
(45,'GYM-0030','Heritage Brown','M',1,20.11,20.11),
(46,'GYM-0022','Moss Olive/Core Olive','M',1,22.34,22.34),
(47,'GYM-0031','Asphalt Grey/Black/Spray Dye','S',1,14.89,14.89),
(48,'GYM-0032','Black','L',1,38.23,38.23),
(49,'GYM-0033','Butter Yellow','S',1,37.23,37.23),
(50,'GYM-0033','Butter Yellow','M',1,37.23,37.23),
(51,'GYM-0007','Butter Yellow','XS-S',1,34.75,34.75),
(52,'GYM-0034','Dusk Purple/Wash','M',1,27.80,27.80),
(53,'GYM-0035','Steady Purple','S',1,23.58,23.58),
(54,'GYM-0035','Steady Purple','M',1,23.58,23.58),
(55,'GYM-0036','Black/Asphalt Grey','XS-S',1,29.79,29.79),
(56,'GYM-0036','Black/Asphalt Grey','S',2,29.79,59.58),
(57,'GYM-0036','Black/Asphalt Grey','M',2,29.79,59.58),
(58,'GYM-0036','Electric Pink/Sorbet Yellow','S',2,29.79,59.58),
(59,'GYM-0036','Electric Pink/Sorbet Yellow','XS-S',1,29.79,29.79),
(60,'GYM-0037','Cherry Purple/Reset Pink','XS-S',2,27.80,55.60),
(61,'GYM-0037','Cherry Purple/Reset Pink','M',1,27.80,27.80),
(62,'GYM-0037','Cherry Purple/Reset Pink','L',1,27.80,27.80),
(63,'GYM-0037','Cherry Purple/Reset Pink','S',1,27.80,27.80),
(64,'GYM-0038','Black','S',2,28.55,57.10),
(65,'GYM-0038','Black','M',2,28.55,57.10),
(66,'GYM-0039','Tinted White/Cherry Purple','XS-S',2,29.79,59.58),
(67,'GYM-0039','Tinted White/Cherry Purple','S',2,29.79,59.58),
(68,'GYM-0039','Black/Tinted White','XS-S',2,29.79,59.58),
(69,'GYM-0039','Black/Tinted White','S',2,29.79,59.58),
(70,'GYM-0040','Black','S',2,27.30,54.60),
(71,'GYM-0040','Black','M',2,27.30,54.60),
(72,'GYM-0041','Super-Set Pink','S',2,17.38,34.76),
(73,'GYM-0041','Super-Set Pink','M',2,17.38,34.76),
(74,'GYM-0041','Super-Set Pink','XS-S',1,17.38,17.38),
(75,'GYM-0042','Force Blue/Steady Purple','S',2,21.84,43.68),
(76,'GYM-0042','Force Blue/Steady Purple','M',2,21.84,43.68),
(77,'GYM-0042','Focus Pink/Tinted White','S',2,21.84,43.68),
(78,'GYM-0042','Focus Pink/Tinted White','M',2,21.84,43.68),
(79,'GYM-0042','Black/Smokey Grey','S',1,21.84,21.84),
(80,'GYM-0043','Super-Set Pink','S',2,18.62,37.24),
(81,'GYM-0043','Super-Set Pink','M',2,18.62,37.24),
(82,'GYM-0044','Cherry Purple/Wash','XS-S',3,34.75,104.25),
(83,'GYM-0044','Cherry Purple/Wash','S',2,34.75,69.50),
(84,'GYM-0044','Cherry Purple/Wash','M',1,34.75,34.75),
(85,'GYM-0034','Dusk Purple/Wash','XS-S',3,27.80,83.40),
(86,'GYM-0034','Dusk Purple/Wash','S',2,27.80,55.60),
(87,'GYM-0045','Focus Pink/Tinted White','S',2,29.79,59.58),
(88,'GYM-0045','Focus Pink/Tinted White','M',2,29.79,59.58),
(89,'GYM-0045','Force Blue/Steady Purple','S',2,29.79,59.58),
(90,'GYM-0045','Black','S',2,29.79,59.58),
(91,'GYM-0046','Strength Pink','One Size',4,32.77,131.08),
(92,'GYM-0047','Carmine Red','One Size',2,14.89,29.78),
(93,'GYM-0048','Chilled Blue','One Size',2,12.41,24.82),
(94,'GYM-0049','Reset Pink','M',2,5.21,10.42),
(95,'GYM-0049','Stone Beige','S',2,5.21,10.42),
(96,'GYM-0050','Discipline Green','S',2,11.17,22.34),
(97,'GYM-0051','Super-Set Pink/Focus Pink','One Size',2,12.41,24.82),
(98,'GYM-0052','White','One Size',2,18.62,37.24),
(99,'GYM-0053','Black/Asphalt Grey','S',2,21.84,43.68),
(100,'GYM-0053','Black/Asphalt Grey','XS-S',1,21.84,21.84),
(101,'GYM-0054','Black','S',2,27.30,54.60),
(102,'GYM-0055','Black/Focus Pink','M',2,27.30,54.60),
(103,'GYM-0056','Black','M',1,22.84,22.84),
(104,'GYM-0057','Conditioning Red','S',2,22.34,44.68),
(105,'GYM-0057','Force Blue','S',2,22.34,44.68),
(106,'GYM-0058','Force Blue','XS-S',2,22.34,44.68),
(107,'GYM-0058','Force Blue','S',2,22.34,44.68),
(108,'GYM-0059','Calm Pink','One Size',2,12.41,24.82),
(109,'GYM-0060','Tinted White','One Size',2,16.38,32.76),
(110,'GYM-0061','Black','One Size',3,6.95,20.85),
(111,'GYM-0062','Soft White','S',2,8.69,17.38),
(112,'GYM-0062','Soft White','M',1,8.69,8.69),
(113,'GYM-0062','Pebble Grey','S',2,8.69,17.38),
(114,'GYM-0062','Pebble Grey','M',1,8.69,8.69),
(115,'GYM-0058','Force Blue','M',2,22.34,44.68),
(116,'GYM-0058','Black/Conditioning Red','XS-S',2,22.34,44.68),
(117,'GYM-0058','Black/Conditioning Red','S',2,22.34,44.68),
(118,'GYM-0058','Black/Conditioning Red','M',2,22.34,44.68),
(119,'GYM-0058','Athletic Red','XS-S',2,22.34,44.68),
(120,'GYM-0058','Athletic Red','S',2,22.34,44.68),
(121,'GYM-0058','Athletic Red','M',2,22.34,44.68),
(122,'GYM-0063','Super-Set Pink','XS-S',2,24.82,49.64),
(123,'GYM-0063','Super-Set Pink','S',2,24.82,49.64),
(124,'GYM-0064','Black/Asphalt Grey','S',1,23.58,23.58),
(125,'GYM-0064','Black/Asphalt Grey','XS-S',1,23.58,23.58),
(126,'GYM-0064','Electric Pink/Sorbet Yellow','XS-S',1,23.58,23.58),
(127,'GYM-0064','Electric Pink/Sorbet Yellow','S',1,23.58,23.58),
(128,'GYM-0064','Black/Asphalt Grey','M',1,23.58,23.58),
(129,'GYM-0065','Black/Asphalt Grey','S',1,27.30,27.30),
(130,'GYM-0065','Black/Asphalt Grey','M',1,27.30,27.30),
(131,'GYM-0065','Electric Pink/Sorbet Yellow','S',1,27.30,27.30),
(132,'GYM-0066','Electric Pink','M',1,18.62,18.62),
(133,'GYM-0067','Black','S',1,18.62,18.62),
(134,'GYM-0068','Soft White','M',2,9.93,19.86),
(135,'GYM-0069','White/Black','S',2,14.89,29.78),
(136,'GYM-0070','Poise Pink','S',1,29.79,29.79),
(137,'GYM-0070','Poise Pink','M',1,29.79,29.79),
(138,'GYM-0007','Track Green','S',2,34.75,69.50),
(139,'GYM-0007','Track Green','XS-S',1,34.75,34.75),
(140,'GYM-0007','Poise Pink','S',2,34.75,69.50),
(141,'GYM-0007','Poise Pink','M',2,34.75,69.50),
(142,'GYM-0071','Track Green','S',1,27.80,27.80),
(143,'GYM-0071','Track Green','XS-S',1,27.80,27.80),
(144,'GYM-0072','Rich Maroon/Marl','S',2,27.80,55.60),
(145,'GYM-0072','Rich Maroon/Marl','M',2,27.80,55.60),
(146,'GYM-0073','Black Marl','S',2,14.15,28.30),
(147,'GYM-0074','White','S',2,14.89,29.78),
(148,'GYM-0074','Black','S',2,14.89,29.78),
(149,'GYM-0075','Iron Blue','S',2,32.77,65.54),
(150,'GYM-0075','Iron Blue','M',2,32.77,65.48);

START TRANSACTION;

SET @linea_id = (SELECT id FROM lineas_ropa WHERE nombre_linea COLLATE utf8mb4_unicode_ci = 'GymShark' COLLATE utf8mb4_unicode_ci ORDER BY id LIMIT 1);
SET @proveedor_count = (SELECT COUNT(*) FROM proveedores WHERE tienda_proveedor COLLATE utf8mb4_unicode_ci = 'Gymshark Oficial' COLLATE utf8mb4_unicode_ci);
SET @existing_purchase_count = (SELECT COUNT(*) FROM compras WHERE codigo_compra = 'CMP-GYMSHARK-20261007-01');
SET @existing_code_count = (SELECT COUNT(*) FROM productos p JOIN tmp_gym_products t ON t.codigo_producto = p.codigo_producto);
SET @category_conflict_count = (
  SELECT COUNT(*) FROM (
    SELECT c.nombre_categoria, c.linea_ropa_id, COUNT(*) AS n
    FROM categorias_productos c
    JOIN tmp_gym_categories t ON t.nombre_categoria COLLATE utf8mb4_unicode_ci = c.nombre_categoria COLLATE utf8mb4_unicode_ci
    WHERE c.linea_ropa_id = @linea_id
    GROUP BY c.nombre_categoria, c.linea_ropa_id
    HAVING COUNT(*) > 1
  ) x
);
SET @color_conflict_count = (
  SELECT COUNT(*) FROM (
    SELECT c.nombre_color, COUNT(*) AS n
    FROM colores c
    JOIN tmp_gym_colors t ON t.nombre_color COLLATE utf8mb4_unicode_ci = c.nombre_color COLLATE utf8mb4_unicode_ci
    GROUP BY c.nombre_color
    HAVING COUNT(*) > 1
  ) x
);
SET @preflight_ok = IF(COALESCE(@linea_id,0) = 1 AND @proveedor_count <= 1 AND @existing_purchase_count = 0 AND @existing_code_count = 0 AND @category_conflict_count = 0 AND @color_conflict_count = 0, 1, 0);

SELECT 'PREFLIGHT' AS control, @preflight_ok AS ok, @linea_id AS linea_id, @proveedor_count AS proveedor_existente, @existing_purchase_count AS compra_existente, @existing_code_count AS codigos_existentes, @category_conflict_count AS conflictos_categorias, @color_conflict_count AS conflictos_colores;

INSERT INTO proveedores (tienda_proveedor,nombres_proveedor,apellidos_proveedor,cedula_proveedor,telefono_proveedor,direccion_proveedor,email_proveedor,estado_proveedor,created_at,updated_at)
SELECT 'Gymshark Oficial','Gymshark','Oficial',NULL,NULL,NULL,NULL,'1',NOW(),NOW()
FROM (SELECT 1 AS n) s
WHERE @preflight_ok = 1
  AND NOT EXISTS (SELECT 1 FROM proveedores p WHERE p.tienda_proveedor COLLATE utf8mb4_unicode_ci = 'Gymshark Oficial' COLLATE utf8mb4_unicode_ci);

SET @proveedor_id = (SELECT id FROM proveedores WHERE tienda_proveedor COLLATE utf8mb4_unicode_ci = 'Gymshark Oficial' COLLATE utf8mb4_unicode_ci ORDER BY id LIMIT 1);

INSERT INTO categorias_productos (nombre_categoria,linea_ropa_id,estado_categoria,created_at,updated_at)
SELECT t.nombre_categoria,@linea_id,'1',NOW(),NOW()
FROM tmp_gym_categories t
LEFT JOIN categorias_productos c
  ON c.nombre_categoria COLLATE utf8mb4_unicode_ci = t.nombre_categoria COLLATE utf8mb4_unicode_ci
 AND c.linea_ropa_id = @linea_id
WHERE @preflight_ok = 1 AND c.id IS NULL;

INSERT INTO colores (nombre_color,codigo_color,created_at,updated_at)
SELECT t.nombre_color,t.codigo_color,NOW(),NOW()
FROM tmp_gym_colors t
LEFT JOIN colores c
  ON c.nombre_color COLLATE utf8mb4_unicode_ci = t.nombre_color COLLATE utf8mb4_unicode_ci
WHERE @preflight_ok = 1 AND c.id IS NULL;

INSERT INTO compras (codigo_compra,descripcion_compra,envio_compra,total_pesos_compra,proveedor_id,estado_compra,estado_recepcion,total_dolares_compra,importacion_compra,total_final_compra,created_at,updated_at)
SELECT 'CMP-GYMSHARK-20261007-01','Compra Gymshark en tránsito - pedido consolidado imágenes 1 a 4',0,0,@proveedor_id,'1','EN_TRANSITO',4238.90,1022.00,5260.90,NOW(),NOW()
FROM (SELECT 1 AS n) s
WHERE @preflight_ok = 1;

SET @compra_id = (SELECT id FROM compras WHERE codigo_compra = 'CMP-GYMSHARK-20261007-01' ORDER BY id LIMIT 1);

INSERT INTO productos
(codigo_producto,descripcion_producto,imagen_producto,cantidad_compra_producto,stock_venta_producto,precio_pesos_producto,precio_dolares_producto,precio_venta_producto,precio_promocional,promocion_activa,promocion_fecha_inicio,promocion_fecha_fin,promocion_etiqueta,compras_id,estado_producto,publicado_ecommerce,created_at,updated_at,categoria_producto_id)
SELECT t.codigo_producto,t.descripcion_producto,NULL,CAST(t.cantidad_compra_producto AS CHAR),CAST(0 AS CHAR),0,t.costo_prorrateado,0,NULL,0,NULL,NULL,NULL,@compra_id,'1',0,NOW(),NOW(),c.id
FROM tmp_gym_products t
JOIN categorias_productos c
  ON c.nombre_categoria COLLATE utf8mb4_unicode_ci = t.categoria COLLATE utf8mb4_unicode_ci
 AND c.linea_ropa_id = @linea_id
WHERE @preflight_ok = 1;

INSERT INTO colores_productos
(producto_id,colores_id,cantidad_por_color,stock_por_color,talla_por_color,created_at,updated_at)
SELECT p.id,c.id,CAST(v.cantidad AS CHAR),CAST(0 AS CHAR),v.talla,NOW(),NOW()
FROM tmp_gym_variants v
JOIN productos p ON p.codigo_producto = v.codigo_producto AND p.compras_id = @compra_id
JOIN colores c ON c.nombre_color COLLATE utf8mb4_unicode_ci = v.nombre_color COLLATE utf8mb4_unicode_ci
WHERE @preflight_ok = 1;

INSERT INTO compra_items
(compra_id,producto_id,colores_id,talla,cantidad,stock_ingresado,costo_unitario_pesos,costo_unitario_dolares,subtotal_pesos,subtotal_dolares,estado,observacion,metadata,created_at,updated_at)
SELECT @compra_id,p.id,c.id,v.talla,v.cantidad,0,0,v.costo_unitario_dolares,0,v.subtotal_dolares,'ACTIVO','Compra Gymshark en tránsito - pendiente recepción física',NULL,NOW(),NOW()
FROM tmp_gym_variants v
JOIN productos p ON p.codigo_producto = v.codigo_producto AND p.compras_id = @compra_id
JOIN colores c ON c.nombre_color COLLATE utf8mb4_unicode_ci = v.nombre_color COLLATE utf8mb4_unicode_ci
WHERE @preflight_ok = 1;

SELECT 'CARGA_TEST_ANTES_ROLLBACK' AS control,
       @preflight_ok AS preflight_ok,
       @compra_id AS compra_id,
       (SELECT COUNT(*) FROM categorias_productos c WHERE c.linea_ropa_id = @linea_id AND c.nombre_categoria COLLATE utf8mb4_unicode_ci IN (SELECT nombre_categoria FROM tmp_gym_categories)) AS categorias,
       (SELECT COUNT(*) FROM colores c JOIN tmp_gym_colors t ON t.nombre_color COLLATE utf8mb4_unicode_ci = c.nombre_color COLLATE utf8mb4_unicode_ci) AS colores,
       (SELECT COUNT(*) FROM productos WHERE compras_id = @compra_id) AS productos,
       (SELECT COUNT(*) FROM colores_productos cp JOIN productos p ON p.id = cp.producto_id WHERE p.compras_id = @compra_id) AS variantes,
       (SELECT COALESCE(SUM(cantidad),0) FROM compra_items WHERE compra_id = @compra_id) AS piezas,
       (SELECT COALESCE(SUM(subtotal_dolares),0) FROM compra_items WHERE compra_id = @compra_id) AS subtotal_items,
       (SELECT COUNT(*) FROM inventario_movimientos im WHERE im.compra_id = @compra_id AND im.tipo_movimiento = 'ENTRADA_COMPRA') AS entradas_inventario,
       (SELECT COUNT(*) FROM productos WHERE compras_id = @compra_id AND (stock_venta_producto <> 0 OR publicado_ecommerce <> 0 OR precio_venta_producto <> 0)) AS productos_con_estado_incorrecto,
       (SELECT COUNT(*) FROM colores_productos cp JOIN productos p ON p.id = cp.producto_id WHERE p.compras_id = @compra_id AND cp.stock_por_color <> '0') AS variantes_con_stock_incorrecto,
       (SELECT COUNT(*) FROM compra_items WHERE compra_id = @compra_id AND stock_ingresado <> 0) AS items_con_stock_incorrecto;

ROLLBACK;
SELECT 'ROLLBACK_REALIZADO' AS control;
