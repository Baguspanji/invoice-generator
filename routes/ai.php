<?php

use App\Mcp\Servers\IceSumServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/icesum', IceSumServer::class)->middleware('mcp.token');
Mcp::local('icesum', IceSumServer::class);
