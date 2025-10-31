
-- --------------------------------------------------------
-- Database Schema: dbm_*
-- Author: Design by Malina
-- Schema Optimization: Code Copilot
--
-- Encoding: utf8mb4 / Collation: utf8mb4_unicode_ci
--
-- Collation options:
--   utf8mb4_unicode_ci - najlepsza zgodność językowa, poprawne sortowanie diakrytyków.
--   utf8mb4_general_ci - szybsze, ale mniej dokładne przy sortowaniu (np. znaków narodowych).
--   utf8mb4_polish_ci  - zalecane dla projektów z dominującym językiem polskim.
--
-- INFO: For MySQL 8.0 and later versions, the status can be changed to "CHECK" (ENUM for migration)
-- `status` VARCHAR(20) NOT NULL DEFAULT 'new' CHECK (`status` IN ('active','inactive','new')),
-- --------------------------------------------------------

-- TABLE: dbm_user
CREATE TABLE IF NOT EXISTS `dbm_user` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `login` VARCHAR(64) NOT NULL,
  `email` VARCHAR(180) NOT NULL,
  `password` VARCHAR(128) NOT NULL,
  `roles` VARCHAR(10) NOT NULL DEFAULT 'USER',
  `token` VARCHAR(50) DEFAULT NULL,
  `verified` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_login` (`login`),
  UNIQUE KEY `uq_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dbm_user` (`id`, `login`, `email`, `password`, `roles`, `token`, `verified`, `created_at`) VALUES
(1, 'Admin', 'admin@mail.com', '$2y$10$30xGoLBAGXNwt5mSb2CU0uJ/hsrHlHVHCsWo3TF2wXGWVuqw3PR/m', 'ADMIN', '50a9ead33e94f4b56cd9475483ce9105e8a5bf6f', 1, '2025-01-01 12:00:00'),
(2, 'John', 'john@mail.com', '$2y$10$YENFQ6axxkDxPyvhEYLBX.ld46LupE6sO7to91glQL0ZxU9XyA.yK', 'USER', '545932a772cae4455b882a4cb6551c7ba0c7b6a3', 1, '2025-01-02 12:00:00'),
(3, 'Lucy', 'lucy@mail.com', '$2y$10$YENFQ6axxkDxPyvhEYLBX.ld46LupE6sO7to91glQL0ZxU9XyA.yK', 'USER', 'ac8fe01e8de57f4c0d1d54cc7a2bcd871d3f7dee', 0, '2025-01-03 12:00:00');

-- TABLE: dbm_user_details
CREATE TABLE IF NOT EXISTS `dbm_user_details` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `fullname` VARCHAR(100) DEFAULT NULL,
  `profession` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `website` VARCHAR(120) DEFAULT NULL,
  `avatar` VARCHAR(50) DEFAULT NULL,
  `biography` TEXT DEFAULT NULL,
  `business` VARCHAR(100) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_details_user_id` (`user_id`),
  CONSTRAINT `fk_user_details_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `dbm_user` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dbm_user_details` (`id`, `user_id`, `fullname`, `profession`, `phone`, `website`, `avatar`, `biography`, `business`, `address`) VALUES
(1, 1, 'Arthur Malinowski', 'Fullstack Developer', '+48 600 000 000', 'www.dbm.org.pl', 'avatar-1.jpg', 'This is the story of a designer and entrepreneur. A completely Polish, virtuous story about style and creativity, focused on the power of love for passion.', 'Design by Malina Ltd.', 'Raspberry Land, PL'),
(2, 2, 'John Doe', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 3, 'Lucy Johansson', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- TABLE: dbm_article_categories
CREATE TABLE IF NOT EXISTS `dbm_article_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `category_name` VARCHAR(100) NOT NULL,
  `category_description` TEXT NOT NULL,
  `category_keywords` VARCHAR(255) NOT NULL,
  `image_filename` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dbm_article_categories` (`id`, `category_name`, `category_description`, `category_keywords`, `image_filename`) VALUES
(1, 'Web Design', 'Morbi tempus iaculis urna id volutpat lacus laoreet non curabitur. Morbi enim nunc faucibus a pellentesque sit amet porttitor. Molestie a iaculis at erat pellentesque adipiscing commodo. Accumsan sit amet nulla facilisi morbi tempus iaculis urna id. Mauris cursus mattis molestie a iaculis at erat pellentesque. Elit ullamcorper dignissim cras tincidunt lobortis. Porta non pulvinar neque laoreet suspendisse interdum consectetur libero. Quam adipiscing vitae proin sagittis nisl. Hendrerit dolor magna eget est lorem. Adipiscing elit ut aliquam purus sit. Quam quisque id diam vel quam elementum pulvinar etiam non. Amet nisl purus in mollis nunc. Tristique senectus et netus et malesuada fames. Molestie ac feugiat sed lectus vestibulum. Ut pharetra sit amet aliquam id. Leo integer malesuada nunc vel. Vestibulum mattis ullamcorper velit sed ullamcorper. Arcu cursus euismod quis viverra nibh cras. Ornare arcu odio ut sem nulla pharetra diam sit amet. At tellus at urna condimentum mattis pellentesque id nibh.', 'web design, web, design', 'category-web-design.jpg'),
(2, '3D Graphics', 'Sagittis aliquam malesuada bibendum arcu vitae. Tortor dignissim convallis aenean et tortor at. Tempor orci eu lobortis elementum nibh. Diam donec adipiscing tristique risus nec feugiat in fermentum. Proin sagittis nisl rhoncus mattis rhoncus. Ac tortor vitae purus faucibus ornare suspendisse sed nisi. Adipiscing diam donec adipiscing tristique risus nec feugiat in. Suspendisse ultrices gravida dictum fusce ut placerat orci nulla. Orci porta non pulvinar neque. Eget velit aliquet sagittis id consectetur purus ut faucibus. Id consectetur purus ut faucibus. Nunc lobortis mattis aliquam faucibus purus in.', '3d graphics, 3d, graphics', 'category-3d-graphics.jpg'),
(3, 'Internet Marketing', 'Sit amet cursus sit amet dictum sit amet justo. Id eu nisl nunc mi ipsum faucibus vitae aliquet nec. Pellentesque habitant morbi tristique senectus et netus et malesuada. Nunc mi ipsum faucibus vitae aliquet nec. Congue quisque egestas diam in. Ut venenatis tellus in metus. Turpis cursus in hac habitasse platea dictumst quisque sagittis purus. In dictum non consectetur a erat nam at lectus urna. In iaculis nunc sed augue lacus viverra vitae congue eu. Sagittis orci a scelerisque purus semper eget.', 'internet marketing, internet, marketing', 'category-internet-marketing.jpg'),
(4, 'Lifestyle', 'Tellus id interdum velit laoreet id donec ultrices tincidunt arcu. Sed cras ornare arcu dui vivamus arcu felis. Morbi leo urna molestie at elementum eu. Senectus et netus et malesuada fames ac. Vitae et leo duis ut diam quam nulla. Ante in nibh mauris cursus mattis molestie a. Tempus imperdiet nulla malesuada pellentesque. Odio morbi quis commodo odio aenean sed adipiscing diam. Pellentesque nec nam aliquam sem et tortor consequat id porta. Nisl vel pretium lectus quam id leo in vitae. Donec ultrices tincidunt arcu non sodales neque sodales ut etiam. Tempor nec feugiat nisl pretium fusce id velit ut. Dui nunc mattis enim ut tellus elementum sagittis. Facilisi etiam dignissim diam quis enim lobortis. Suspendisse sed nisi lacus sed viverra tellus in hac. Vulputate eu scelerisque felis imperdiet proin fermentum leo vel orci. Pharetra convallis posuere morbi leo urna molestie at. Venenatis cras sed felis eget velit.', 'lifestyle', 'category-lifestyle.jpg');

-- TABLE: dbm_article
CREATE TABLE IF NOT EXISTS `dbm_article` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `meta_title` VARCHAR(150) NOT NULL,
  `meta_description` VARCHAR(255) NOT NULL,
  `meta_keywords` VARCHAR(255) NOT NULL,
  `page_header` VARCHAR(150) NOT NULL,
  `page_content` TEXT NOT NULL,
  `image_filename` VARCHAR(50) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'new',
  `visit` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_article_user_id` (`user_id`),
  KEY `idx_article_category_id` (`category_id`),
  KEY `idx_article_status_category` (`status`, `category_id`),
  CONSTRAINT `fk_article_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `dbm_user` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_article_category_id`
    FOREIGN KEY (`category_id`) REFERENCES `dbm_article_categories` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dbm_article` (`id`, `user_id`, `category_id`, `meta_title`, `meta_description`, `meta_keywords`, `page_header`, `page_content`, `image_filename`, `status`, `visit`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Lorem ipsum dolor sit amet.', 'lorem ipsum dolor, lorem, ipsum', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. <b>Lorem ipsum dolor sit amet, consectetur adipiscing elit</b>. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arcu eget nulla. <b>Lorem ipsum dolor sit amet, consectetur adipiscing elit</b>. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. <b>Lorem ipsum dolor sit amet, consectetur adipiscing elit</b>. Curabitur sodales ligula in libero. Sed dignissim lacinia nunc. </p>\r\n<p>Curabitur tortor. Pellentesque nibh. Aenean quam. In scelerisque sem at dolor. Maecenas mattis. Sed convallis tristique sem. Proin ut ligula vel nunc egestas porttitor. <b>Curabitur tortor</b>. Morbi lectus risus, iaculis vel, suscipit quis, luctus non, massa. Fusce ac turpis quis ligula lacinia aliquet. Mauris ipsum. Nulla metus metus, ullamcorper vel, tincidunt sed, euismod in, nibh.</p>\r\n<p><i>Lorem ipsum dolor sit amet, consectetur adipiscing elit</i>. Quisque volutpat condimentum velit. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nam nec ante. <b>Morbi lectus risus, iaculis vel, suscipit quis, luctus non, massa</b>. Sed lacinia, urna non tincidunt mattis, tortor neque adipiscing diam, a cursus ipsum ante quis turpis. Nulla facilisi. <b>Proin ut ligula vel nunc egestas porttitor</b>. Ut fringilla. Suspendisse potenti. <i>Aenean quam</i>. Nunc feugiat mi a tellus consequat imperdiet. Vestibulum sapien. Proin quam. Etiam ultrices. <b>Nam nec ante</b>. Suspendisse in justo eu magna luctus suscipit. </p>\r\n<p>Sed lectus. Integer euismod lacus luctus magna. Quisque cursus, metus vitae pharetra auctor, sem massa mattis sem, at interdum magna augue eget diam. <b>Ut fringilla</b>. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Morbi lacinia molestie dui. Praesent blandit dolor. <i>Sed lacinia, urna non tincidunt mattis, tortor neque adipiscing diam, a cursus ipsum ante quis turpis</i>. Sed non quam. In vel mi sit amet augue congue elementum. Morbi in ipsum sit amet pede facilisis laoreet. Donec lacus nunc, viverra nec, blandit vel, egestas et, augue. Vestibulum tincidunt malesuada tellus. Ut ultrices ultrices enim. Curabitur sit amet mauris. Morbi in dui quis est pulvinar ullamcorper. Nulla facilisi. </p>\r\n<p>Integer lacinia sollicitudin massa. Cras metus. Sed aliquet risus a tortor. Integer id quam. Morbi mi. Quisque nisl felis, venenatis tristique, dignissim in, ultrices sit amet, augue. Proin sodales libero eget ante. Nulla quam. Aenean laoreet. Vestibulum nisi lectus, commodo ac, facilisis ac, ultricies eu, pede. Ut orci risus, accumsan porttitor, cursus quis, aliquet eget, justo. Sed pretium blandit orci. <b>Integer id quam</b>. Ut eu diam at pede suscipit sodales. Aenean lectus elit, fermentum non, convallis id, sagittis at, neque. </p>', NULL, 'active', 0, '2025-01-05 12:00:00', NULL),
(2, 2, 1, 'Ergo id est convenienter naturae vivere, a natura discedere', 'Nunc vides, quid faciat. Igitur ne dolorem quidem. Vestri haec verecundius, illi fortasse constantius. Praeclare hoc quidem. Tum Quintus: Est plane, Piso, ut dicis, inquit. Sumenda potius quam expetenda.', 'ergo, naturae vivere, natura discedere', 'Ergo id est convenienter naturae vivere, a natura discedere', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Avaritiamne minuis? <a href=\"#\">Quis Aristidem non mortuum diligit?</a> Duo Reges: constructio interrete. Huius, Lyco, oratione locuples, rebus ipsis ielunior. </p>\r\n<p>Nunc vides, quid faciat. Igitur ne dolorem quidem. Vestri haec verecundius, illi fortasse constantius. Praeclare hoc quidem. Tum Quintus: Est plane, Piso, ut dicis, inquit. Sumenda potius quam expetenda. <a href=\"#\">Primum quid tu dicis breve?</a> Illi enim inter se dissentiunt. Frater et T. Igitur ne dolorem quidem. <a href=\"#\">Non est igitur voluptas bonum.</a> </p>\r\n<p>Quae cum dixisset paulumque institisset, Quid est? Nihil enim iam habes, quod ad corpus referas; Cur deinde Metrodori liberos commendas? Tu enim ista lenius, hic Stoicorum more nos vexat. </p>\r\n<h2>Ad corpus diceres pertinere-, sed ea, quae dixi, ad corpusne refers?</h2>\r\n<p>Primum quid tu dicis breve? Videamus animi partes, quarum est conspectus illustrior; Quid, quod res alia tota est? At enim hic etiam dolore. Scisse enim te quis coarguere possit? Ea possunt paria non esse. Equidem etiam Epicurum, in physicis quidem, Democriteum puto. </p>\r\n<ol>\r\n	<li>Varietates autem iniurasque fortunae facile veteres philosophorum praeceptis instituta vita superabat.</li>\r\n	<li>Quid, quod homines infima fortuna, nulla spe rerum gerendarum, opifices denique delectantur historia?</li>\r\n	<li>Sed ille, ut dixi, vitiose.</li>\r\n	<li>Quod cum ille dixisset et satis disputatum videretur, in oppidum ad Pomponium perreximus omnes.</li>\r\n	<li>Et ille ridens: Video, inquit, quid agas;</li>\r\n</ol>\r\n<p>There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don\'t look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn\'t anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</p>', NULL, 'active', 0, '2025-01-05 13:00:00', '2025-02-01 09:00:00'),
(3, 3, 2, 'Itaque ad tempus ad Pisonem omnes', 'Nunc ita separantur, ut disiuncta sint, quo nihil potest esse perversius. Quonam modo? Quod mihi quidem visus est, cum sciret, velle tamen confitentem audire Torquatum.', 'itaque, tempus, pisonem omnes', 'Itaque ad tempus ad Pisonem omnes', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Conferam tecum, quam cuique verso rem subicias; Illud dico, ea, quae dicat, praeclare inter se cohaerere. Nunc ita separantur, ut disiuncta sint, quo nihil potest esse perversius. Quonam modo? Quod mihi quidem visus est, cum sciret, velle tamen confitentem audire Torquatum. Duo Reges: constructio interrete. Sed plane dicit quod intellegit. Igitur neque stultorum quisquam beatus neque sapientium non beatus. Non est ista, inquam, Piso, magna dissensio. Quam illa ardentis amores excitaret sui! Cur tandem? Quicquid porro animo cernimus, id omne oritur a sensibus;</p>\r\n<p>Quonam, inquit, modo? Frater et T. <i>Sedulo, inquam, faciam.</i> Quid est igitur, inquit, quod requiras? <strong>Refert tamen, quo modo.</strong> At habetur! Et ego id scilicet nesciebam! Sed ut sit, etiamne post mortem coletur? Nec vero hoc oratione solum, sed multo magis vita et factis et moribus comprobavit. </p>\r\n<ol>\r\n	<li>Non enim solum Torquatus dixit quid sentiret, sed etiam cur.</li>\r\n	<li>Scripta sane et multa et polita, sed nescio quo pacto auctoritatem oratio non habet.</li>\r\n	<li>Fortasse id optimum, sed ubi illud: Plus semper voluptatis?</li>\r\n	<li>Vide, quantum, inquam, fallare, Torquate.</li>\r\n</ol>\r\n<p>Fatebuntur Stoici haec omnia dicta esse praeclare, neque eam causam Zenoni desciscendi fuisse. <mark>Certe non potest.</mark> Partim cursu et peragratione laetantur, congregatione aliae coetum quodam modo civitatis imitantur; Inquit, an parum disserui non verbis Stoicos a Peripateticis, sed universa re et tota sententia dissidere? Satis est tibi in te, satis in legibus, satis in mediocribus amicitiis praesidii. Quid de Platone aut de Democrito loquar? Vitiosum est enim in dividendo partem in genere numerare. Quo plebiscito decreta a senatu est consuli quaestio Cn.</p>\r\n<ul>\r\n	<li>Id et fieri posse et saepe esse factum et ad voluptates percipiendas maxime pertinere.</li>\r\n	<li>Graecis hoc modicum est: Leonidas, Epaminondas, tres aliqui aut quattuor;</li>\r\n	<li>Tu quidem reddes;</li>\r\n	<li>Ergo et avarus erit, sed finite, et adulter, verum habebit modum, et luxuriosus eodem modo.</li>\r\n</ul>\r\n<p>Quonam, inquit, modo? Gloriosa stentatio in constituendo summo bono. </p>\r\n<blockquote class=\"blockquote\">Is cum arderet podagrae doloribus visitassetque hominem Charmides Epicureus perfamiliaris et tristis exiret, Mane, quaeso, inquit, Charmide noster;</blockquote>\r\n<p><i>Idem iste, inquam, de voluptate quid sentit?</i> Negat esse eam, inquit, propter se expetendam. Atque ab his initiis profecti omnium virtutum et originem et progressionem persecuti sunt. Nulla erit controversia. Restincta enim sitis stabilitatem voluptatis habet, inquit, illa autem voluptas ipsius restinctionis in motu est. Dici enim nihil potest verius.</p>', NULL, 'active', 0, '2025-01-05 14:00:00', '2025-02-01 10:00:00'),
(4, 1, 3, 'Neque viverra justo nec ultrices dui sapien eget', 'Neque viverra justo nec ultrices dui sapien eget. Iaculis at erat pellentesque adipiscing commodo elit at imperdiet. Maecenas sed enim ut sem viverra aliquet eget sit. Faucibus turpis in eu mi bibendum neque.', 'neque viverra, justo nec ultrices dui sapien eget, Iaculis at erat pellentesque', 'Neque viverra justo nec ultrices dui sapien eget', '<p><img src=\"[URL]images/blog/photo/post-web.jpg\" class=\"img-fluid\" alt=\"Neque viverra justo nec ultrices dui sapien eget\"></p>\r\n<p><strong>Neque viverra justo nec ultrices dui sapien eget.</strong> Iaculis at erat pellentesque adipiscing commodo elit at imperdiet. Maecenas sed enim ut sem viverra aliquet eget sit. Faucibus turpis in eu mi bibendum neque. Egestas erat imperdiet sed euismod nisi porta lorem. <strong>Pellentesque diam volutpat commodo sed egestas egestas fringilla phasellus.</strong>Risus pretium quam vulputate dignissim suspendisse in est ante. Scelerisque fermentum dui faucibus in ornare quam. <strong>Lectus nulla at volutpat diam.</strong> Morbi blandit cursus risus at ultrices. Laoreet suspendisse interdum consectetur libero id faucibus nisl tincidunt eget. Ullamcorper malesuada proin libero nunc consequat interdum varius sit amet. Ut aliquam purus sit amet. Aenean pharetra magna ac placerat vestibulum lectus mauris. Tincidunt lobortis feugiat vivamus at augue eget arcu dictum. Hendrerit gravida rutrum quisque non tellus orci ac auctor. Est placerat in egestas erat imperdiet sed euismod nisi porta. Tellus in metus vulputate eu scelerisque felis imperdiet proin.</p>\r\n<dl>\r\n <dt>Definition list</dt>\r\n <dd>Consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</dd>\r\n <dt>Lorem ipsum dolor sit amet</dt>\r\n <dd>Consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</dd>\r\n</dl>\r\n<ul>\r\n <li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit.</li>\r\n <li>Aliquam tincidunt mauris eu risus.</li>\r\n <li>Vestibulum auctor dapibus neque.</li>\r\n</ul>\r\n<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. <mark>Donec eu libero sit amet</mark> quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>', 'post-web.jpg', 'new', 0, '2025-01-05 15:00:00', '2025-02-01 11:00:00'),
(5, 1, 1, 'Praesent euismod gravida libero, a luctus nisi fermentum nec', 'Praesent euismod gravida libero, a luctus nisi fermentum nec. Donec ac eleifend libero. Vivamus sollicitudin molestie velit, vel dapibus ipsum vehicula in. Vestibulum eu arcu sit amet libero tristique sollicitudin.', 'praesent, euismod, gravida, libero', 'Praesent euismod gravida libero, a luctus nisi fermentum nec', '<p><img src=\"[URL]images/blog/photo/post-idea.jpg\" class=\"img-fluid\" alt=\"Praesent euismod gravida libero\"></p>\r\n<p><strong>Praesent euismod gravida libero</strong>, a luctus nisi fermentum nec. Donec ac eleifend libero. Vivamus sollicitudin molestie velit, vel dapibus ipsum vehicula in. Vestibulum eu arcu sit amet libero tristique sollicitudin. Integer pulvinar in felis vitae facilisis. Donec at justo ex. <b>Phasellus luctus</b>, orci sed fringilla tincidunt, lectus ligula placerat elit, sed commodo libero diam vel augue.</p>\r\n<p>Vestibulum convallis consequat faucibus. <mark>Phasellus posuere molestie quam a auctor.</mark> Curabitur consectetur sapien ac efficitur feugiat. Ut euismod tincidunt viverra. Phasellus laoreet vel neque nec pretium. Nam et lacinia lectus. Nullam consequat faucibus nibh eu commodo. Ut risus nulla, ultrices nec condimentum nec, vestibulum tempor est. Maecenas et sagittis lorem, eget cursus nisl. Praesent id ipsum libero. Nullam vitae tellus quis lectus posuere consequat porttitor et ipsum. Praesent in leo quis nunc semper hendrerit ut ornare dolor. Curabitur id aliquam orci. Morbi elementum sapien vitae augue malesuada lacinia a sed massa.</p>\r\n<p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. <em>Aenean ultricies mi vitae est.</em> Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, <code>commodo vitae</code>, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. <a href=\"#\">Donec non enim</a> in turpis pulvinar facilisis. Ut felis.</p>\r\n<h2>Header Level 2</h2>\r\n<ol>\r\n <li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit.</li>\r\n <li>Aliquam tincidunt mauris eu risus.</li>\r\n</ol>\r\n<blockquote class=\"blockquote\"><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus magna. Cras in mi at felis aliquet congue. Ut a est eget ligula molestie gravida. Curabitur massa. Donec eleifend, libero at sagittis mollis, tellus est malesuada tellus, at luctus turpis elit sit amet quam. Vivamus pretium ornare est.</p></blockquote>\r\n<h3>Header Level 3</h3>\r\n<ul>\r\n <li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit.</li>\r\n <li>Aliquam tincidunt mauris eu risus.</li>\r\n</ul>\r\n<pre><code>\r\n #header h1 a { display: block; width: 300px; height: 80px; }\r\n</code></pre>\r\n<p>Praesent sagittis urna elementum, finibus magna sit amet, cursus odio. Etiam dictum augue dapibus nisi consectetur volutpat. Quisque consectetur nulla vitae leo facilisis placerat. Aliquam vel dui vitae metus pretium iaculis. Aliquam eu mauris vel leo blandit sollicitudin nec a tortor. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Praesent tincidunt lacus augue, eget bibendum est tincidunt vulputate. Donec et ultricies urna, et venenatis nibh. Donec aliquam leo at tellus mattis viverra. Nullam in tortor diam. Sed id hendrerit ipsum. Suspendisse in lacinia ante, et tincidunt ligula.</p>', 'post-idea.jpg', 'new', 0, '2025-01-05 16:00:00', '2025-02-01 12:00:00');

-- TABLE: dbm_gallery
CREATE TABLE IF NOT EXISTS `dbm_gallery` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `image_filename` VARCHAR(50) NOT NULL,
  `views` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'new',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gallery_user_id` (`user_id`),
  CONSTRAINT `fk_gallery_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `dbm_user` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dbm_gallery` (`id`, `user_id`, `title`, `description`, `image_filename`, `views`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Sunset over the glade', '', 'sunset_663a047db9cdd.jpg', 0, 'active', '2025-01-10 12:01:00', NULL),
(2, 1, 'Pebble beach and sea', '', 'beach_663a04e627e7f.jpg', 0, 'active', '2025-01-10 12:02:00', NULL),
(3, 1, 'Old style street', '', 'street_663a04f2f0509.jpg', 0, 'active', '2025-01-10 12:03:00', NULL),
(4, 1, 'Mountains and trees', '', 'mountains_663a05061748b.jpg', 0, 'active', '2025-01-10 12:04:00', NULL),
(5, 1, 'View of the sea and the city in the distance', '', 'sea_663a05178920f.jpg', 0, 'active', '2025-01-10 12:05:00', NULL),
(6, 1, 'Grape perfect fruit', '', 'grape_663a052c65ee5.jpg', 0, 'active', '2025-01-10 12:06:00', NULL),
(7, 1, 'Stones against the background of the sea', '', 'sea_663a0556e74f4.jpg', 0, 'active', '2025-01-10 12:07:00', NULL),
(8, 1, 'A bicycle against the background of a wooden house', '', 'house_663a0575eb350.jpg', 0, 'active', '2025-01-10 12:08:00', '2025-01-10 15:00:00'),
(9, 1, 'Door to the cathedral', '', 'door_663a058a52539.jpg', 0, 'active', '2025-01-10 12:09:00', '2025-01-10 16:00:00'),
(10, 1, 'Seashells on the beach', '', 'seashells_663a05a0129ce.jpg', 0, 'active', '2025-01-10 12:10:00', '2025-01-10 17:00:00');

-- TABLE: dbm_remember_me
CREATE TABLE IF NOT EXISTS `dbm_remember_me` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` VARCHAR(50) NOT NULL,
  `validator` VARCHAR(100) NOT NULL,
  `expiry` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_selector` (`user_id`, `selector`),
  UNIQUE KEY `uq_selector` (`selector`),
  KEY `idx_remember_user_id` (`user_id`),
  CONSTRAINT `fk_remember_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `dbm_user` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: dbm_reset_password
CREATE TABLE IF NOT EXISTS `dbm_reset_password` (
  `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `email` VARCHAR(180) NOT NULL,
  `token` VARCHAR(100) NOT NULL,
  `expires` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reset_token` (`token`),
  KEY `idx_reset_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
