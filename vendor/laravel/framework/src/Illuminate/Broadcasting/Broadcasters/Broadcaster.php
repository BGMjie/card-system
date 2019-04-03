<?php
 namespace Illuminate\Broadcasting\Broadcasters; use ReflectionFunction; use Illuminate\Support\Str; use Illuminate\Container\Container; use Illuminate\Contracts\Routing\UrlRoutable; use Illuminate\Contracts\Routing\BindingRegistrar; use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException; use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract; abstract class Broadcaster implements BroadcasterContract { protected $channels = []; protected $bindingRegistrar; public function channel($channel, callable $callback) { $this->channels[$channel] = $callback; return $this; } protected function verifyUserCanAccessChannel($request, $channel) { foreach ($this->channels as $pattern => $callback) { if (! Str::is(preg_replace('/\{(.*?)\}/', '*', $pattern), $channel)) { continue; } $parameters = $this->extractAuthParameters($pattern, $channel, $callback); if ($result = $callback($request->user(), ...$parameters)) { return $this->validAuthenticationResponse($request, $result); } } throw new AccessDeniedHttpException; } protected function extractAuthParameters($pattern, $channel, $callback) { $callbackParameters = (new ReflectionFunction($callback))->getParameters(); return collect($this->extractChannelKeys($pattern, $channel))->reject(function ($value, $key) { return is_numeric($key); })->map(function ($value, $key) use ($callbackParameters) { return $this->resolveBinding($key, $value, $callbackParameters); })->values()->all(); } protected function extractChannelKeys($pattern, $channel) { preg_match('/^'.preg_replace('/\{(.*?)\}/', '(?<$1>[^\.]+)', $pattern).'/', $channel, $keys); return $keys; } protected function resolveBinding($key, $value, $callbackParameters) { $newValue = $this->resolveExplicitBindingIfPossible($key, $value); return $newValue === $value ? $this->resolveImplicitBindingIfPossible( $key, $value, $callbackParameters ) : $newValue; } protected function resolveExplicitBindingIfPossible($key, $value) { $binder = $this->binder(); if ($binder && $binder->getBindingCallback($key)) { return call_user_func($binder->getBindingCallback($key), $value); } return $value; } protected function resolveImplicitBindingIfPossible($key, $value, $callbackParameters) { foreach ($callbackParameters as $parameter) { if (! $this->isImplicitlyBindable($key, $parameter)) { continue; } $instance = $parameter->getClass()->newInstance(); if (! $model = $instance->resolveRouteBinding($value)) { throw new AccessDeniedHttpException; } return $model; } return $value; } protected function isImplicitlyBindable($key, $parameter) { return $parameter->name === $key && $parameter->getClass() && $parameter->getClass()->isSubclassOf(UrlRoutable::class); } protected function formatChannels(array $channels) { return array_map(function ($channel) { return (string) $channel; }, $channels); } protected function binder() { if (! $this->bindingRegistrar) { $this->bindingRegistrar = Container::getInstance()->bound(BindingRegistrar::class) ? Container::getInstance()->make(BindingRegistrar::class) : null; } return $this->bindingRegistrar; } } 