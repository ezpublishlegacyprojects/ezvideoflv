<?php

//
// SOFTWARE NAME: eZ Video FLV
// SOFTWARE RELEASE: 0.1
// COPYRIGHT NOTICE: Copyright (C)	1999-2006 eZ Systems AS
// 									2007 Damien POBEL
// BASED ON: ezbinaryfilehander.php
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



include_once( "kernel/classes/datatypes/ezbinaryfile/ezbinaryfile.php" );
include_once( "kernel/classes/ezbinaryfilehandler.php" );

$extension_dir = eZExtension::baseDirectory();
include_once( $extension_dir.'/ezvideoflv/datatypes/ezvideoflv/ezvideoflv.php' );

define( "EZ_FILE_FLV_ID", 'ezflv' );

class eZFLVHandler extends eZBinaryFileHandler
{
    function eZFLVHandler()
    {
        $this->eZBinaryFileHandler( EZ_FILE_FLV_ID, "PHP FLV passtrough", EZ_BINARY_FILE_HANDLE_DOWNLOAD );
    }

    function handleFileDownload( &$contentObject, &$contentObjectAttribute, $type,
                                 $fileInfo )
    {
        $fileName = $fileInfo['filepath_flv'];

        // VS-DBFILE

        require_once( 'kernel/classes/ezclusterfilehandler.php' );
        $file = eZClusterFileHandler::instance( $fileName );

        if ( $fileName != "" and $file->exists() )
        {
            $file->fetch( true );
            $fileSize = $file->size();
            $mimeType =  'video/x-flv';
            $originalFileName = $fileInfo['original_filename'];
			$flvFileName = str_replace( eZFile::suffix( $originalFileName ), 'flv', $originalFileName );
            $contentLength = $fileSize;
            $fileOffset = false;
            $fileLength = false;
            if ( isset( $_SERVER['HTTP_RANGE'] ) )
            {
                $httpRange = trim( $_SERVER['HTTP_RANGE'] );
                if ( preg_match( "/^bytes=([0-9]+)-$/", $httpRange, $matches ) )
                {
                    $fileOffset = $matches[1];
                    header( "Content-Range: bytes $fileOffset-" . $fileSize - 1 . "/$fileSize" );
                    header( "HTTP/1.1 206 Partial content" );
                    $contentLength -= $fileOffset;
                }
            }
            // Figure out the time of last modification of the file right way to get the file mtime ... the
            $fileModificationTime = filemtime( $fileName );

            ob_clean();
            header( "Pragma: " );
            header( "Cache-Control: " );
            /* Set cache time out to 10 minutes, this should be good enough to work around an IE bug */
            header( "Expires: ". gmdate('D, d M Y H:i:s T', time() + 600) . ' GMT' );
            header( "Last-Modified: ". gmdate( 'D, d M Y H:i:s T', $fileModificationTime ) . ' GMT' );
            header( "Content-Length: $contentLength" );
            header( "Content-Type: $mimeType" );
            header( "X-Powered-By: eZ publish" );
            header( "Content-disposition: attachment; filename=\"$flvFileName\"" );
            header( "Content-Transfer-Encoding: binary" );
            header( "Accept-Ranges: bytes" );

            $fh = fopen( "$fileName", "rb" );
            if ( $fileOffset )
            {
                fseek( $fh, $fileOffset );
            }

            ob_end_clean();
            fpassthru( $fh );
            fclose( $fh );

            eZExecution::cleanExit();
        }
        return EZ_BINARY_FILE_RESULT_UNAVAILABLE;
    }
}

?>
