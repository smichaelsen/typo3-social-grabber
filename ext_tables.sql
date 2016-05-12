CREATE TABLE tx_socialgrabber_channel (
    uid int(11) NOT NULL auto_increment,
    pid int(11) NOT NULL default '0',
    title varchar(255) NOT NULL default '',
    grabber_class varchar(3976) default NULL,
    url varchar(2083) NOT NULL default '',
    feed_etag varchar(255) NOT NULL default '',
    feed_last_modified varchar(255) NOT NULL default '',

    crdate int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    PRIMARY KEY (uid)
);

CREATE TABLE tx_socialgrabber_post (
    uid int(11) NOT NULL auto_increment,
    pid int(11) NOT NULL default '0',
    channel int(11) default NULL,
    post_identifier varchar(255) NOT NULL default '',
    publication_date int(11) DEFAULT '0' NOT NULL,
    url varchar(2083) NOT NULL default '',
    title varchar(255) NOT NULL default '',
    teaser varchar(2323) default NULL,
    author varchar(255) default NULL,
    author_url varchar(2083) default NULL,
    PRIMARY KEY (uid),
    UNIQUE KEY post_identifier (post_identifier),
    KEY channel (channel)
);
