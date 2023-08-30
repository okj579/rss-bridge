<?php

class ARDMediathekBridge extends BridgeAbstract
{
    const NAME = 'ARD-Mediathek Bridge';
    const URI = 'https://www.ardmediathek.de';
    const DESCRIPTION = 'Feed of any series in the ARD-Mediathek, specified by its path';
    const MAINTAINER = 'yue-dongchen';
    /*
     * Number of Items to be requested from ARDmediathek API
     * 12 has been observed on the wild
     * 29 is the highest successfully tested value
     * More Items could be fetched via pagination
     * The JSON-field pagination holds more information on that
     * @const PAGESIZE number of requested items
     */
    const PAGESIZE = 3;
    /*
     * The URL Prefix of the (Webapp-)API
     * @const APIENDPOINT https-URL of the used endpoint
     */
    const APIENDPOINT = 'https://api.ardmediathek.de/page-gateway/widgets/ard/asset/{id}/?pageSize={pageSize}';
    /*
     * The URL Prefix of the (Webapp-)API
     * @const APIENDPOINTMEDIA https-URL of the used endpoint
     */
    const APIENDPOINTMEDIA = 'https://api.ardmediathek.de/page-gateway/mediacollection/{id}?devicetype=pc&embedded=true';
    /*
     * The URL prefix of the video link
     * URLs from the webapp include a slug containing titles of show, episode, and tv station.
     * It seems to work without that.
     * @const VIDEOLINK https-URL of video links
     */
    const VIDEOLINK = 'https://www.ardmediathek.de/video/{id}';
    /*
     * The requested width of the preview image
     * 432 has been observed on the wild
     * The webapp seems to also compute and add the height value
     * It seems to works without that.
     * @const IMAGEWIDTH width in px of the preview image
     */
    const IMAGEWIDTH = 432;
    /*
     * Placeholder that will be replace by IMAGEWIDTH in the preview image URL
     * @const IMAGEWIDTHPLACEHOLDER
     */
    const IMAGEWIDTHPLACEHOLDER = '{width}';

    const PARAMETERS = [
        [
            'path' => [
                'name' => 'Show Link or ID',
                'required' => true,
                'title' => 'Link to the show page or just its alphanumeric suffix',
                'defaultValue' => 'https://www.ardmediathek.de/sendung/45-min/Y3JpZDovL25kci5kZS8xMzkx/'
            ]
        ]
    ];

    public function collectData()
    {
        $oldTz = date_default_timezone_get();

        date_default_timezone_set('Europe/Berlin');

        $pathComponents = explode('/', $this->getInput('path'));
        if (empty($pathComponents)) {
            returnClientError('Path may not be empty');
        }
        if (count($pathComponents) < 2) {
            $showID = $pathComponents[0];
        } else {
            $lastKey = count($pathComponents) - 1;
            $showID = $pathComponents[$lastKey];
            if (strlen($showID) === 0) {
                $showID = $pathComponents[$lastKey - 1];
            }
        }

        $url = strtr(self::APIENDPOINT, ['{id}' => $showID, '{pageSize}' => self::PAGESIZE]);
        $rawJSON = getContents($url);
        $processedJSON = json_decode($rawJSON);

        foreach ($processedJSON->teasers as $video) {
            $item = [];
            // there is also ->links->self->id, ->links->self->urlId, ->links->target->id, ->links->target->urlId
            $item['uri'] = strtr(self::VIDEOLINK, ['{id}' => $video->id]);
            // there is also ->mediumTitle and ->shortTitle
            $item['title'] = $video->longTitle;

            $mediaUrl = strtr(self::APIENDPOINTMEDIA, ['{id}' => $video->id]);
            $mediaData = json_decode(getContents($mediaUrl));

            $selectedMedia = null;
            foreach ($mediaData->_mediaArray[0]->_mediaStreamArray as $candidate) {
                if (!$selectedMedia || ($candidate->_quality !== 'auto' && $candidate->_quality > $selectedMedia->_quality)) {
                    $selectedMedia = $candidate;
                }
            }

            $item['enclosures'] = [
                // in the test, aspect16x9 was the only child of images, not sure whether that is always true
                str_replace(self::IMAGEWIDTHPLACEHOLDER, self::IMAGEWIDTH, $video->images->aspect16x9->src),
                $selectedMedia->_stream
            ];
            $item['content'] = '<img src="' . $item['enclosures'][0] . '" /><p>';
            $item['timestamp'] = $video->broadcastedOn;
            $item['uid'] = $video->id;
            $item['author'] = $video->publicationService->name;
            $this->items[] = $item;
        }

        date_default_timezone_set($oldTz);
    }
}
