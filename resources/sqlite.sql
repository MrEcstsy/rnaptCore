-- #!sqlite

-- # { players
-- #  { initialize
CREATE TABLE IF NOT EXISTS core_players (
    uuid VARCHAR(36) PRIMARY KEY,
    username VARCHAR(16),
    balance INT DEFAULT 0,
    cooldowns TEXT
);
-- #  }

-- #  { select
SELECT *
FROM core_players;
-- #  }

-- #  { create
-- #      :uuid string
-- #      :username string
-- #      :balance int
-- #      :cooldowns string
INSERT OR REPLACE INTO core_players(uuid, username, balance, cooldowns)
VALUES (:uuid, :username, :balance, :cooldowns);
-- #  }

-- #  { update
-- #      :uuid string
-- #      :username string
-- #      :balance int
-- #      :cooldowns string
UPDATE core_players
SET username = :username,
    balance = :balance,
    cooldowns = :cooldowns
WHERE uuid = :uuid;
-- #  }

-- #  { delete
-- #      :uuid string
DELETE FROM core_players
WHERE uuid = :uuid;
-- #  }
-- # }

-- # { warps
-- #  { initialize
CREATE TABLE IF NOT EXISTS warps (
    warp_name VARCHAR(32) PRIMARY KEY NOT NULL,
    world_name VARCHAR(32) NOT NULL,
    x INT NOT NULL,
    y INT NOT NULL,
    z INT NOT NULL,
    settings TEXT
    );
-- #  }
-- #  { select
SELECT *
FROM warps;
-- #  }
-- #  { create
-- #      :warp_name string
-- #      :world_name string
-- #      :x int
-- #      :y int
-- #      :z int
-- #      :settings string
INSERT OR REPLACE INTO warps(warp_name, world_name, x, y, z, settings)
VALUES (:warp_name, :world_name, :x, :y, :z, :settings);
-- #  }
-- #  { delete
-- #      :warp_name string
DELETE FROM warps
WHERE warp_name=:warp_name;
-- #  }
-- # }

-- # { coinflip
-- #  { initialize
CREATE TABLE IF NOT EXISTS CoinFlips (
    uuid VARCHAR(36) NOT NULL,
    username VARCHAR(16) NOT NULL,
    type INT NOT NULL,
    money INT NOT NULL,
    PRIMARY KEY (uuid, type)  -- or just uuid if you want one entry per player
);
-- #  }
-- #  { select
SELECT *
FROM CoinFlips;
-- #  }

-- #  { create
-- #      :uuid string
-- #      :username string
-- #      :type int
-- #      :money int
INSERT OR REPLACE INTO CoinFlips (uuid, username, type, money)
VALUES (:uuid, :username, :type, :money);
-- #  }
-- #  { delete
-- #      :uuid string
DELETE FROM CoinFlips
WHERE uuid=:uuid;
-- #  }
-- # }

-- # { areas 
-- #  { initialize
CREATE TABLE IF NOT EXISTS areas (
    area_name VARCHAR(32) PRIMARY KEY,
    x1 INT NOT NULL,
    y1 INT NOT NULL,
    z1 INT NOT NULL,
    x2 INT NOT NULL,
    y2 INT NOT NULL,
    z2 INT NOT NULL,
    world_name VARCHAR(32) NOT NULL,
    settings TEXT NOT NULL
    );
-- #  }

-- # { select 
SELECT * FROM areas;
-- # }

-- # { create 
-- #      :area_name string
-- #      :x1 int
-- #      :y1 int
-- #      :z1 int
-- #      :x2 int
-- #      :y2 int
-- #      :z2 int
-- #      :world_name string
-- #      :settings string
INSERT OR REPLACE INTO areas(area_name, x1, y1, z1, x2, y2, z2, world_name, settings)
VALUES (:area_name, :x1, :y1, :z1, :x2, :y2, :z2, :world_name, :settings);
-- # }

-- # { update
-- #      :area_name string
-- #      :x1 int
-- #      :y1 int
-- #      :z1 int
-- #      :x2 int
-- #      :y2 int
-- #      :z2 int
-- #      :world_name string
-- #      :settings string
UPDATE areas
SET x1 = :x1,
    y1 = :y1,
    z1 = :z1,
    x2 = :x2,
    y2 = :y2,
    z2 = :z2,
    world_name = :world_name,
    settings = :settings
WHERE area_name = :area_name;
-- # }5

-- # { delete
-- #      :area_name string
DELETE FROM areas
WHERE area_name=:area_name;
-- # }
-- # }