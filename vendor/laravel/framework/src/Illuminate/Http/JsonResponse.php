<?php
 namespace Illuminate\Http; use JsonSerializable; use InvalidArgumentException; use Illuminate\Support\Traits\Macroable; use Illuminate\Contracts\Support\Jsonable; use Illuminate\Contracts\Support\Arrayable; use Symfony\Component\HttpFoundation\JsonResponse as BaseJsonResponse; class JsonResponse extends BaseJsonResponse { use ResponseTrait, Macroable { Macroable::__call as macroCall; } public function __construct($data = null, $status = 200, $headers = [], $options = 0) { $this->encodingOptions = $options; parent::__construct($data, $status, $headers); } public function withCallback($callback = null) { return $this->setCallback($callback); } public function getData($assoc = false, $depth = 512) { return json_decode($this->data, $assoc, $depth); } public function setData($data = []) { $this->original = $data; if ($data instanceof Jsonable) { $this->data = $data->toJson($this->encodingOptions); } elseif ($data instanceof JsonSerializable) { $this->data = json_encode($data->jsonSerialize(), $this->encodingOptions); } elseif ($data instanceof Arrayable) { $this->data = json_encode($data->toArray(), $this->encodingOptions); } else { $this->data = json_encode($data, $this->encodingOptions); } if (! $this->hasValidJson(json_last_error())) { throw new InvalidArgumentException(json_last_error_msg()); } return $this->update(); } protected function hasValidJson($jsonError) { return $jsonError === JSON_ERROR_NONE || ($jsonError === JSON_ERROR_UNSUPPORTED_TYPE && $this->hasEncodingOption(JSON_PARTIAL_OUTPUT_ON_ERROR)); } public function setEncodingOptions($options) { $this->encodingOptions = (int) $options; return $this->setData($this->getData()); } public function hasEncodingOption($option) { return (bool) ($this->encodingOptions & $option); } } 