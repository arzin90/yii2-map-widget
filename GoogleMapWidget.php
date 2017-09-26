<?php

namespace common\components;

use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\web\View;

/**
 * @property Model $model base yii2 model or ActiveRecord objects
 */
class GoogleMapWidget extends \yii\base\Widget
{
    /* @var $google_api_key string  Google map api key */
    public $google_api_key = 'AIzaSyBoVEvqNU_AP2HEjpMTWkerMfqGpOOray0';

    /**
     * @var string $latAttribute Latitude attribute
     */
    public $latAttribute = 'lat';

    /**
     * @var string $lngAttribute Longitude attribute
     */
    public $lngAttribute = 'lng';

    /**
     * @var string $address Address attribute
     */
    public $address = null;

    /**
     * @var string $mapCanvasId Map canvas id
     */
    public $mapCanvasId = null;

    /**
     * @var integer $mapWidth Width of the map canvas
     */
    public $mapWidth = null;

    /**
     * @var integer $mapHeight Height of the map canvas
     */
    public $mapHeight = null;

    /**
     * @var float $lat Latitude for the map
     */
    public $lat = null;

    /**
     * @var float $lng Longitude for the map
     */
    public $lng = null;

    /**
     * @var integer $zoom Zoom for the map
     */
    public $zoom = null;

    /**
     * @var object $model Object model
     */
    public $model = null;

    /**
     * @var array $canvasOptions canvas attribute options: ex. ['class'=>'custom-class']
     */
    public $canvasOptions = [];

    /**
     * @var boolean $staticMap
     */
    public $staticMap = true;

    /**
     * @doc https://developers.google.com/maps/documentation/javascript/markers
     * @var array $iconOptions : ex. ['url'=>'image_path','size'=>[width, height],'origin'=>[x,y],'anchor'=>[x,y]]
     *
     */
    public $iconOptions = [];

    public function init()
    {
        parent::init();

        $this->model = (isset($this->model)) ? $this->model : null;

        if (is_null($this->model)) {
            throw new InvalidConfigException('model must be set.');
        }

        $formName = strtolower($this->model->formName());

        $this->latAttribute = $this->latAttribute ? $formName . '-' . $this->latAttribute : $formName . '-' . 'lat';
        $this->lngAttribute = $this->lngAttribute ? $formName . '-' . $this->lngAttribute : $formName . '-' . 'lng';
        $this->address = (isset($this->address)) ? $formName . '-' . $this->address : $formName . '-' . 'address';
        $this->mapCanvasId = (isset($this->mapCanvasId)) ? $this->mapCanvasId : 'map';

        $this->iconOptions['url'] = ArrayHelper::keyExists('url', $this->iconOptions) ? $this->iconOptions['url'] : false;
        $this->iconOptions['size'] = ArrayHelper::keyExists('size', $this->iconOptions) && count($this->iconOptions['size']) == 2 ? $this->iconOptions['size'] : [20, 32];
        $this->iconOptions['origin'] = ArrayHelper::keyExists('origin', $this->iconOptions) && count($this->iconOptions['origin']) == 2 ? $this->iconOptions['origin'] : [0, 0];
        $this->iconOptions['anchor'] = ArrayHelper::keyExists('anchor', $this->iconOptions) && count($this->iconOptions['anchor']) == 2 ? $this->iconOptions['anchor'] : [0, 32];

        $this->mapWidth = (isset($this->mapWidth)) ? $this->mapWidth : 450;
        $this->mapHeight = (isset($this->mapHeight)) ? $this->mapHeight : 300;
        $this->lat = (isset($this->lat)) ? $this->lat : 19.4326077;
        $this->lng = (isset($this->lng)) ? $this->lng : -99.13320799999997;
        $this->zoom = (isset($this->zoom)) ? $this->zoom : 8;

        $this->registerAsset();
    }

    public function run()
    {
        $js = <<<SCRIPT

    		var map = null;
    		var marker = null;
    		var markers = [];
            var geoCoder;
            var input = document.getElementById('$this->address');
            var place=null;
            var image=null;

			function initMap() {
                        
			  	var mapOptions = {
		            zoom: $this->zoom,
		            center: {lat: $this->lat, lng: $this->lng},
		            mapTypeId: google.maps.MapTypeId.ROADMAP
		        };		

		        if('{$this->iconOptions["url"]}'){
                    image = {
                        url: '{$this->iconOptions["url"]}',
                        size: new google.maps.Size('{$this->iconOptions["size"][0]}', '{$this->iconOptions["size"][1]}'),
                        origin: new google.maps.Point('{$this->iconOptions["origin"][0]}', '{$this->iconOptions["origin"][1]}'),
                        anchor: new google.maps.Point('{$this->iconOptions["anchor"][0]}', '{$this->iconOptions["anchor"][1]}')
                    };
		        }

                geoCoder = new google.maps.Geocoder();
				map = new google.maps.Map(document.getElementById('$this->mapCanvasId'), mapOptions);

                if('$this->staticMap'){
                     marker = new google.maps.Marker({
                        position:{lat: $this->lat, lng: $this->lng},
                        draggable: false,
                        map: map,
                        icon: image
                    });
                    return;
                }                 

                var autoComplete = new google.maps.places.Autocomplete(input);     
                   
                placeMarker(map.getCenter(),map);                
                autoComplete.bindTo('bounds', map);

                autoComplete.addListener('place_changed', function() {
                    var address = '';
                    
                    if(marker){
                      marker.setVisible(false);
                    }

                    place = autoComplete.getPlace();

                    if (!place.geometry) {
                        window.alert("No details available for input: '" + place.name + "'");
                        return;
                    }

                    placeMarker(place.geometry.location, map);

                    if (place.geometry.viewport) {
                        map.fitBounds(place.geometry.viewport);
                    } else {
                        map.setCenter(place.geometry.location);
                        map.setZoom(17);
                    }
                });
			}

			initMap();

            function geocodePosition(pos) {
                geoCoder.geocode({
                latLng: pos
                }, function(responses) {
                   input.value = responses[0].formatted_address;
                });
            }

			function placeMarker(position, map) {

			    if(marker){
                    marker.setVisible(false);
                }
 
                marker = new google.maps.Marker({
                    position: position,
                    draggable: true,
                    map: map,
                    icon: image
                });

                geocodePosition(position);

                document.getElementById('$this->latAttribute').value = position.lat();
                document.getElementById('$this->lngAttribute').value = position.lng();

                google.maps.event.addListener(marker, 'dragend', function(e) {
                    geocodePosition(marker.getPosition());

                    document.getElementById('$this->latAttribute').value = e.latLng.lat();
                    document.getElementById('$this->lngAttribute').value = e.latLng.lng();
                });

                marker.setVisible(true);
                map.panTo(position);
                markers.push(marker);
                markers[0].setMap(map);
			}
SCRIPT;

        $this->getView()->registerJs($js);
        echo $this->wrapperMap();
    }

    /**
     * @return string
     */
    private function wrapperMap()
    {
        $options = ArrayHelper::merge($this->canvasOptions, [
            'id' => $this->mapCanvasId,
            'style' => sprintf('width:%spx;height:%spx;', $this->mapWidth, $this->mapHeight)
        ]);

        $mapHtml = Html::tag('div', '', $options);
        $mapHtml .= Html::activeHiddenInput($this->model, ltrim($this->latAttribute, strtolower($this->model->formName() . '-')));
        $mapHtml .= Html::activeHiddenInput($this->model, ltrim($this->lngAttribute, strtolower($this->model->formName() . '-')));

        return $mapHtml;
    }

    private function registerAsset()
    {
        $view = $this->getView();
        $view->registerJsFile(sprintf('https://maps.googleapis.com/maps/api/js?key=%s&libraries=places', $this->google_api_key), ['position' => View::POS_HEAD]);
    }
}
