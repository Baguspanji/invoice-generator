<?php

use App\Mcp\Servers\IceSumServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/icesum', IceSumServer::class);
Mcp::local('icesum', IceSumServer::class);
