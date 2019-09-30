CREATE TABLE IF NOT EXISTS `worker_process` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `host_num_id` int(11) unsigned NOT NULL COMMENT 'supervisor的host的id,从配置文件关联进来',
    `process_name` varchar(100) NOT NULL COMMENT '进程名称,与supervisor的ini配置文件的program一致',
    `hash_key` varchar(100) NOT NULL COMMENT 'process_name的md5生产key',
    `file_ini_name` varchar(100) NOT NULL COMMENT 'supervisor的ini配置文件名',
    `shell_file` varchar(100) NOT NULL COMMENT '控制的脚本名称或者路径',
    `description` varchar(255) NOT NULL COMMENT '进程描述',
    `create_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:01',
    `update_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:01',
    PRIMARY KEY (`id`),
    INDEX (`host_num_id`),
    INDEX (`process_name`,`hash_key`),
    INDEX (`file_ini_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '进程记录表';
