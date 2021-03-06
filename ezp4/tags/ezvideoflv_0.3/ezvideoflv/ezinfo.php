<?php
//
// $Id$
// $HeadURL$
//
// SOFTWARE NAME: eZ Video FLV
// SOFTWARE RELEASE: 0.3
// COPYRIGHT NOTICE: Copyright (C) 2007-2009 Damien POBEL
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//

class eZVideoFLVInfo
{
    static function info()
    {
        return array( 'Name' => "eZ Video FLV extension",
                      'Version' => "0.3",
                      'Copyright' => "Copyright (C) 1999-2009 Damien POBEL",
                      'License' => "GNU General Public License v2.0",
                      'Includes the following third-party software' => array( 'Name' => 'FLV Player',
                                                                              'Version' => '1.3.1',
                                                                              'License' => 'MPL 1.1 - Neolao',
                                                                              'For more information' => 'http://flv-player.net/' )
                      );
    }
}
?>
