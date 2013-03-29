--
-- Table structure for table `docketminder_cases`
--

CREATE TABLE IF NOT EXISTS `docketminder_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `number` varchar(50) NOT NULL,
  `url` varchar(200) NOT NULL,
  `tracked_by` varchar(100) NOT NULL,
  `date_tracked` datetime NOT NULL,
  `last_changed` datetime NOT NULL,
  `last_tracked` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=47 ;

-- --------------------------------------------------------

--
-- Table structure for table `docketminder_users`
--

CREATE TABLE IF NOT EXISTS `docketminder_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(500) NOT NULL,
  `username` varchar(200) NOT NULL,
  `password` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

