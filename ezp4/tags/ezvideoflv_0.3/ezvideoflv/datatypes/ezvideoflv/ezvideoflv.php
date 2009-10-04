<?php
//
// $Id$
// $HeadURL$
//
// SOFTWARE NAME: eZ Video FLV
// SOFTWARE RELEASE: 0.3
// COPYRIGHT NOTICE: Copyright (C)    1999-2006 eZ Systems AS
//                                    2007-2009 Damien POBEL
// BASED ON: ezmedia.php
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


/*!
  \class eZVideoFLV ezvideoflv.php
  \ingroup eZDatatype
  \brief The class eZVideoFLV handles registered media files and their flv version

*/

class eZVideoFLV extends eZPersistentObject
{
    function eZVideoFLV( $row )
    {
        $this->eZPersistentObject( $row );
    }

    static function definition()
    {
        return array( "fields" => array( "contentobject_attribute_id" => array( 'name' => "ContentObjectAttributeID",
                                                                                'datatype' => 'integer',
                                                                                'default' => 0,
                                                                                'required' => true,
                                                                                'foreign_class' => 'eZContentObjectAttribute',
                                                                                'foreign_attribute' => 'id',
                                                                                'multiplicity' => '1..*' ),
                                         "version" => array( 'name' => "Version",
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         "flv" => array( 'name' => "FLV",
                                                              'datatype' => 'string',
                                                              'default' => '',
                                                              'required' => true ),
                                         "filename" => array( 'name' => "Filename",
                                                              'datatype' => 'string',
                                                              'default' => '',
                                                              'required' => true ),
                                         "original_filename" => array( 'name' => "OriginalFilename",
                                                                       'datatype' => 'string',
                                                                       'default' => '',
                                                                       'required' => true ),
                                         "mime_type" => array( 'name' => "MimeType",
                                                               'datatype' => 'string',
                                                               'default' => '',
                                                               'required' => true ),
                                         "width" => array( 'name' => "Width",
                                                           'datatype' => 'integer',
                                                           'default' => 0,
                                                           'required' => true ),
                                         "height" => array( 'name' => "Height",
                                                            'datatype' => 'integer',
                                                            'default' => 0,
                                                            'required' => true ),
                                         "serialized_metadata" => array( 'name' => "SerializedMetadata",
                                                            'datatype' => 'string',
                                                            'default' => '',
                                                            'required' => true ) ),
                      "keys" => array( "contentobject_attribute_id", "version" ),
                      'function_attributes' => array( 'filesize' => 'filesize',
                                                      'filepath' => 'filepath',
                                                      'filesize_flv' => 'filesizeflv',
                                                      'filepath_flv' => 'filepathflv',
                                                      'preview' => 'preview',
                                                      'has_flv' => 'hasflv',
                                                      'mime_type_category' => 'mimeTypeCategory',
                                                      'mime_type_part' => 'mimeTypePart',
                                                      'metadata' => 'getMetadata',
                                                      'duration' => 'getDuration'),
                      "relations" => array( "contentobject_attribute_id" => array( "class" => "ezcontentobjectattribute",
                                                                                   "field" => "id" ),
                                            "version" => array( "class" => "ezcontentobjectattribute",
                                                                "field" => "version" ) ),
                      "class_name" => "eZVideoFLV",
                      "name" => "ezvideoflv" );
    }

    function getMetadata()
    {
        $attrValue = unserialize( $this->attribute( 'serialized_metadata') ) ;
        return $attrValue;
    }

    function preview()
    {
        $fileInfo = $this->storedFileInfo();
        if ( $fileInfo['filename'] == '' )
            return '';
        $storage_dir = eZSys::storageDirectory();
        $ini = eZINI::instance( 'ezvideoflv.ini' );
        $preview_dir = $storage_dir . '/' . $ini->variable( 'Preview', 'Path' );
        $format = $ini->variable( 'Preview', 'Format' );
        $frame = $ini->variable( 'Preview', 'Frame' );
        $oldumask = umask( 0 );
        if ( !is_dir( $preview_dir ) && !eZDir::mkdir( $preview_dir, false, true ) )
        {
            eZDebug::writeError( "Can't create $preview_dir", 'eZVideoFLV' );
            umask( $oldumask );
            return '';
        }
        umask( $oldumask );

        $videoFile = eZClusterFileHandler::instance( $fileInfo['filepath'] );
        if ( ! $videoFile->exists() )
            return '';
        $videoFile->fetch();

        $imgPath = $preview_dir . '/' . $fileInfo['filename'] . '.' . strtolower( $format );
        $file = eZClusterFileHandler::instance( $imgPath );
        if ( ! $file->exists() )
            $imgPath = $this->generatePreview( $imgPath, $fileInfo['filepath'], $format, $frame );
        return $imgPath;
    }

    function generatePreview( $imgPath, $videoFile, $format, $frame)
    {
        $function = 'image' . $format;
        if ( !function_exists( $function ) )
            return '';
        eZDebug::writeDebug( 'Generating preview for ' . $videoFile );
        $ffmpeg = eZVideoFLV::getFFMPEGObject( $videoFile );
        $frame_count = $ffmpeg->getFrameCount() - 1;
        $frame_number = 1;
        if ( is_numeric( $frame ) )
        {
            $frame_number = min( $frame, $frame_count );
            $frame_number = max( 1, $frame_number );
        }
        else
            $frame_number = ceil( $frame_count / 2 );
        eZDebug::writeDebug( 'Using frame ' . $frame_number, 'eZVideoFLV' );
        $frame = $ffmpeg->getFrame( $frame_number );
        $gd_image = $frame->toGDImage();
        $ret = @call_user_func( $function, $gd_image, $imgPath );
        if ( $ret )
        {
            $fileHandler = eZClusterFileHandler::instance();
            $fileHandler->fileStore( $imgPath, 'media', true, 'image/' . $format );
            return $imgPath;
        }
        eZDebug::writeError( "Can't create preview image from GD ressource", 'eZVideoFLV' );
        return '';
    }

    function fileSizeFLV()
    {
        $fileInfo = $this->storedFileInfo();
        $file = eZClusterFileHandler::instance( $fileInfo['filepath_flv'] );

        if ( $file->exists() )
            $fileSize = $file->size();
        else
            $fileSize = 0;
        return $fileSize;
    }

    function fileSize()
    {
        $fileInfo = $this->storedFileInfo();

        $file = eZClusterFileHandler::instance( $fileInfo['filepath'] );

        if ( $file->exists() )
            $fileSize = $file->size();
        else
            $fileSize = 0;
        return $fileSize;
    }

    function filePath()
    {
        $fileInfo = $this->storedFileInfo();
        return $fileInfo['filepath'];
    }

    function filePathFLV()
    {
        $fileInfo = $this->storedFileInfo();
        return $fileInfo['filepath_flv'];
    }

    function hasFLV()
    {
        $fileInfo = $this->storedFileInfo();
        return ( $fileInfo['flv'] != '' );
    }


    function mimeTypeCategory()
    {
        $types = explode( "/", eZPersistentObject::attribute( "mime_type" ) );
        return $types[0];
    }

    function mimeTypePart()
    {
        $types = explode( "/", eZPersistentObject::attribute( "mime_type" ) );
        return $types[1];
    }

    static function create( $contentObjectAttributeID, $version )
    {
        $row = array( "contentobject_attribute_id" => $contentObjectAttributeID,
                      "version" => $version,
                      "flv" => "",
                      "filename" => "",
                      "original_filename" => "",
                      "mime_type" => "",
                      "width" => "0",
                      "height" => "0"
                      );
        return new eZVideoFLV( $row );
    }

    static function fetchVideo( $id, $version, $asObject = true )
    {
        if( $version == null )
        {
            return eZPersistentObject::fetchObjectList( eZVideoFLV::definition(),
                                                        null,
                                                        array( "contentobject_attribute_id" => $id ),
                                                        null,
                                                        null,
                                                        $asObject );
        }
        else
        {
            return eZPersistentObject::fetchObject( eZVideoFLV::definition(),
                                                    null,
                                                    array( "contentobject_attribute_id" => $id,
                                                           "version" => $version ),
                                                    $asObject );
        }
    }

    static function fetchNotConverted()
    {
        return eZPersistentObject::fetchObjectList( eZVideoFLV::definition(),
                                                    null,
                                                    array( "flv" => '' ) );
    }

    static function fetchAll()
    {
        return eZPersistentObject::fetchObjectList( eZVideoFLV::definition() );
    }


    static function removeVideo( $id, $version )
    {
        if( $version == null )
        {
            eZPersistentObject::removeObject( eZVideoFLV::definition(),
                                              array( "contentobject_attribute_id" => $id ) );
        }
        else
        {
            eZPersistentObject::removeObject( eZVideoFLV::definition(),
                                              array( "contentobject_attribute_id" => $id,
                                                     "version" => $version ) );
        }
    }

    /**
     * Get the FFMPEG_Movie object from a video file
     * @return FFMPEG_Movie object
     */
    static function getFFMPEGObject( $videoFile )
    {
        eZVideoFLV::loadFFMPEG();
        $ffmpeg = new FFMPEG_movie( $videoFile, false );
        return $ffmpeg;
    }

    /**
     * Check if ffmpeg.so is loaded and try to load if necessary
     */
    static function loadFFMPEG()
    {
        $extension = 'ffmpeg';
        $extension_soname = $extension . "." . PHP_SHLIB_SUFFIX;
        if( !extension_loaded( $extension ) )
        {
            eZDebug::writeDebug( 'Try to load '.$extension_soname, 'eZVideoFLV' );
            if ( ! @dl( $extension_soname ) )
                eZDebug::writeError( "Can't load FFMPEG module", 'eZVideoFLV' );
        }
        else
            eZDebug::writeDebug( $extension_soname . ' already loaded', 'eZVideoFLV' );
    }

    static function convert( $videoFile, $destinationPath )
    {
        $ini = eZINI::instance( 'ezvideoflv.ini' );
        $alwaysUseCronjob = $ini->variable( 'Convert', 'AlwaysUseCronjob' );
        $useCronjobSize   = $ini->variable( 'Convert', 'UseCronjobSize' );
        if ( $alwaysUseCronjob == 'true' )
            return null;
        $file = eZClusterFileHandler::instance( $videoFile );
        if ( !$file->exists() )
            return null;
        $videoSize = $file->size() / ( 1024 * 1024 );
        eZDebug::writeDebug( 'Video size: ' . $videoSize . ' useCronjobSize: ' . $useCronjobSize, 'eZVideoFLV' );
        if ( !is_numeric( $useCronjobSize ) || ( $videoSize <= $useCronjobSize ) )
            return eZVideoFLV::doConvert( $videoFile, $destinationPath );
        eZDebug::writeDebug( 'Conversion deferred to cron', 'eZVideoFLV' );
        return null;
    }

    /**
     * Convert the video file to FLV and return the filename of the new file
     * @param $videoFile : full path of the original file
     * @param $destinationPath : where to put the flv file
     */
    static function doConvert( $videoFile, $destinationPath )
    {
        eZDebug::writeDebug( 'Call to doConvert(' . $videoFile . ', ' . $destinationPath . ')', 'eZVideoFLV' );
        $file = eZClusterFileHandler::instance( $videoFile );
        if ( ! $file->exists() )
            return null;
        $file->fetch();
        $mimeData = eZMimeType::findByFileContents( $videoFile );
        list( $group, $suffix ) = explode( '/', $mimeData['name'] );
        if ( $group != 'video' )
            return null;
        if ( $mimeData['name'] == 'video/x-flv' )
        {
            // already a flv file
            eZDebug::writeNotice( 'The file ' . $videoFile . ' is already a flv file', 'eZVideoFLV' );
            return basename( $videoFile );
        }
        $flvFile = md5( mt_rand() . microtime() ) . '.flv';
        $flvFileFullPath = $destinationPath . '/' . $flvFile;
        $commandLine = eZVideoFLV::buildCommandLine( $videoFile, $flvFileFullPath );
        $retCode = 0;
        eZDebug::writeDebug( 'Execute ' . $commandLine, 'eZVideoFLV' );
        $output = system( $commandLine, $retCode );
        if ( $retCode != 0 )
        {
            eZDebug::writeError( 'Failed to execute ' . $commandLine . "\nOutput:\n" . $output, "eZVideoFLV" );
            return null;
        }
        return $flvFile;
    }

    /**
     * Build the command line according to settings
     * @param $original_file : original full file path
     * @param $converted_file : converted full file path
     */
    static function buildCommandLine( $original_file, $converted_file )
    {
        $ini = eZINI::instance( 'ezvideoflv.ini' );
        $program = $ini->variable( 'Converter', 'Program' );
        $options = $ini->variable( 'Converter', 'Options' );
        $command = $program;
        $array_search = array();
        $array_search[] = '<original_file>';
        $array_search[] = '<converted_file>';
        $array_replace = array();
        $array_replace[] = $original_file;
        $array_replace[] = $converted_file;
        foreach( $options as $opt )
        {
            $optStr = str_replace( $array_search, $array_replace, $opt );
            $command .= ' ' . $optStr;
        }
        return $command;
    }

    function storedFileInfo()
    {
        $fileName = $this->attribute( 'filename' );
        $flv      = $this->attribute( 'flv' );
        $mimeType = $this->attribute( 'mime_type' );
        $originalFileName = $this->attribute( 'original_filename' );
        $hasFLV  = ( $flv != '' );

        $storageDir = eZSys::storageDirectory();

        $group = '';
        $type = '';
        if ( $mimeType )
            list( $group, $type ) = explode( '/', $mimeType );

        $filePath = $storageDir . '/original/' . $group . '/' . $fileName;
        $filePathFLV = $storageDir . '/original/' . $group . '/' . $flv;

        return array( 'filename' => $fileName,
                      'flv' => $flv,
                      'has_flv' => $hasFLV,
                      'original_filename' => $originalFileName,
                      'filepath' => $filePath,
                      'filepath_flv' => $filePathFLV,
                      'mime_type' => $mimeType );
    }
    
    static function generateMetadata( $file )
    {
        $metadata = false;
        if ( file_exists( $file ) )
        {
            $ffmpeg = eZVideoFLV::getFFMPEGObject( $file );
            eZDebug::writeDebug( $ffmpeg );
            $metadata['duration']           = $ffmpeg->getDuration();
            $metadata['frame_count']        = $ffmpeg->getFrameCount();
            $metadata['frame_rate']         = $ffmpeg->getFrameRate();
            $metadata['comment']            = $ffmpeg->getComment();
            $metadata['title']              = $ffmpeg->getTitle();
            $metadata['author']             = $ffmpeg->getAuthor();
            $metadata['copyright']          = $ffmpeg->getCopyright();
            $metadata['frame_height']       = $ffmpeg->getFrameHeight();
            $metadata['frame_width']        = $ffmpeg->getFrameWidth();
            $metadata['pixel_format']       = $ffmpeg->getPixelFormat();
            $metadata['bit_rate']           = $ffmpeg->getBitRate();
            $metadata['video_bit_rate']     = $ffmpeg->getVideoBitRate();
            $metadata['video_codec']        = $ffmpeg->getVideoCodec();
            $metadata['has_audio']          = $ffmpeg->hasAudio();
            $metadata['audio_bit_rate']     = $metadata['has_audio'] ? $ffmpeg->getAudioBitRate() : false;
            $metadata['audio_sample_rate']  = $metadata['has_audio'] ? $ffmpeg->getAudioSampleRate() : false;
            $metadata['audio_codec']        = $metadata['has_audio'] ? $ffmpeg->getAudioCodec() : false;
            $metadata['audio_channels']     = $metadata['has_audio'] ? $ffmpeg->getAudioChannels() : false;
        }
        return $metadata;
    }

    function getDuration()
    {
        $duration = ezi18n( 'ezvideoflv/datatype', "Unable to determine duration" );
        $metadata = $this->attribute( 'metadata' );
        if ($metadata && is_array( $metadata ) && isset( $metadata['duration'] ) )
          $duration = eZVideoFLV::sec2hms( $metadata['duration'] );
        return $duration;
    }

    static function sec2hms ($sec, $padHours = false )
    {

        // holds formatted string
        $hms = "";
    
        // there are 3600 seconds in an hour, so if we
        // divide total seconds by 3600 and throw away
        // the remainder, we've got the number of hours
        $hours = intval( intval( $sec ) / 3600 ); 

        // add to $hms, with a leading 0 if asked for
        $hms .= ( $padHours ) 
              ? str_pad( $hours, 2, "0", STR_PAD_LEFT ) . ':'
              : $hours . ':';
     
        // dividing the total seconds by 60 will give us
        // the number of minutes, but we're interested in 
        // minutes past the hour: to get that, we need to 
        // divide by 60 again and keep the remainder
        $minutes = intval( ( $sec / 60 ) % 60 );

        // then add to $hms (with a leading 0 if needed)
        $hms .= str_pad( $minutes, 2, "0", STR_PAD_LEFT ) . ':';

        // seconds are simple - just divide the total
        // seconds by 60 and keep the remainder
        $seconds = intval( $sec % 60 ); 

        // add to $hms, again with a leading 0 if needed
        $hms .= str_pad( $seconds, 2, "0", STR_PAD_LEFT );

        // done!
        return $hms;
    }


    public $ContentObjectAttributeID;
    public $FLV;
    public $Filename;
    public $OriginalFilename;
    public $MimeType;
    public $Width;
    public $Height;
    public $SerializedMetadata;
}

?>
