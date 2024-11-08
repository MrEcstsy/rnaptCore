<?php

namespace ecstsy\rnaptCore\utils;

class QueryStmts {

        // PLAYER QUERY
        public const PLAYERS_INIT   = "players.initialize";
        public const PLAYERS_SELECT = "players.select";
        public const PLAYERS_CREATE = "players.create";
        public const PLAYERS_UPDATE = "players.update";
        public const PLAYERS_DELETE = "players.delete";

        // WARPS QUERY 
        public const WARPS_INIT   = "warps.initialize";
        public const WARPS_SELECT = "warps.select";
        public const WARPS_CREATE = "warps.create";
        public const WARPS_UPDATE = "warps.update";
        public const WARPS_DELETE = "warps.delete";

        // COINFLIP QUERY
        public const COINFLIP_INIT   = "coinflip.initialize";
        public const COINFLIP_SELECT = "coinflip.select";
        public const COINFLIP_CREATE = "coinflip.create";
        public const COINFLIP_DELETE = "coinflip.delete";

        // AREA GUARD QUERY
        public const AREA_INIT   = "areas.initialize";
        public const AREA_SELECT = "areas.select";
        public const AREA_CREATE = "areas.create";
        public const AREA_UPDATE = "areas.update";
        public const AREA_DELETE = "areas.delete";
}