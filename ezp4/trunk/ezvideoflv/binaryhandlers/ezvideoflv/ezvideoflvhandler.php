<?php
/*
 * $Id$
 * $HeadURL$
 *
 */

class eZVideoFLVHandler extends eZFilePassthroughHandler
{
    const TYPE_PREVIEW = 1;
    const TYPE_FLV = 2;

    const HANDLER_ID = 'ezvideoflv';

    function eZVideoFLVHandler()
    {
        $this->eZBinaryFileHandler( self::HANDLER_ID, "eZ Video FLV", eZBinaryFileHandler::HANDLE_DOWNLOAD );
    }

    function handleFileDownload( $contentObject, $contentObjectAttribute, $type,
                                 $fileInfo )
    {
        if ( $type != self::TYPE_FLV && $type != self::TYPE_PREVIEW )
        {
            return parent::handleFileDownload( $contentObject, $contentObjectAttribute, $type,
                                               $fileInfo );
        }
        if ( $type === self::TYPE_PREVIEW )
        {
            return $this->handleFileDownloadPreview( $contentObject, $contentObjectAttribute, $type,
                                                     $fileInfo );
        }
        elseif ( $type === self::TYPE_FLV )
        {
            return $this->handleFileDownloadFLV( $contentObject, $contentObjectAttribute, $type,
                                                 $fileInfo );
        }
        eZDebug::writeError( 'Unknown type ' . $type, __METHOD__ );
        return false;
    }

    protected function handleFileDownloadPreview( $contentObject, $contentObjectAttribute, $type,
                                                  $fileInfo )
    {
        $video = eZVideoFLV::fetchVideo( $contentObjectAttribute->attribute( 'id' ),
                                    $contentObjectAttribute->attribute( 'version' ) );
        $fileName = $video->attribute( 'preview' );

        $file = eZClusterFileHandler::instance( $fileName );

        if ( $fileName != "" and $file->exists() )
        {
            $file->fetch();
            $fileSize = $file->size();
            $mimeData = eZMimeType::findByFileContents( $fileName );
            $mimeType = $mimeData['name'];
            $contentLength = $fileSize;
            $fileModificationTime = filemtime( $fileName );

            ob_clean();
            header( "Last-Modified: ". gmdate( 'D, d M Y H:i:s T', $fileModificationTime ) . ' GMT' );
            header( "Content-Length: $contentLength" );
            header( "Content-Type: $mimeType" );
            header( "X-Powered-By: eZ Publish" );

            $fh = fopen( "$fileName", "rb" );
            ob_end_clean();
            fpassthru( $fh );
            fclose( $fh );

            eZExecution::cleanExit();
        }
        return self::RESULT_UNAVAILABLE;
    }

    protected function handleFileDownloadFLV( $contentObject, $contentObjectAttribute, $type,
                                              $fileInfo )
    {
        $fileName = $fileInfo['filepath_flv'];

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
            header( "X-Powered-By: eZ Publish" );
            $dispositionType = self::dispositionType( $mimeType );
            header( "Content-disposition: $dispositionType; filename=\"$flvFileName\"" );
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
        return self::RESULT_UNAVAILABLE;
    }


}


?>
