<?php
namespace SxRequireJs\View\Helper;

use Zend\View\Helper\AbstractHelper,
	Zend\View\Model\ViewModel;

/**
 *  
 */
class RequireJs extends AbstractHelper
{
	protected $modules 		= array();
	protected $applications = array();
	protected $baseUrl 	    = 'js';

	public function __invoke()
	{
		return $this;
		$viewModel = new ViewModel();
	}

	public function __toString()
	{
		return $this->getConfig() . PHP_EOL . $this->getMain();
	}

	public function setBaseUrl($url)
	{
		$this->baseUrl = $url;
	}

	public function getConfig()
	{
		return $this->renderConfig();
	}

	public function getMain()
	{
		return $this->renderMain();
	}

	public function addPaths($paths)
	{
		foreach ($paths as $path) {
			$this->addPath($path);
		}

		return $this;
	}

	public function addModule($moduleName, $path = null)
	{
		return $this->addPath($moduleName, $path);
	}

	public function addPath($moduleName, $path = null)
	{
		if (null === $path) {
			$path = $moduleName . '/js';
		} else {
			$path = trim($path, '/');
		}

		$this->modules[$moduleName] = $path;

		return $this;
	}

	public function addApplication($applicationId, $priority = 1)
	{
		$this->applications[$applicationId] = array (
			'applicationId' => $applicationId,
			'priority'		=> $priority,
		);

		return $this;
	}

	protected function renderConfig()
	{
		if (empty($this->modules)) {
			return '';
		}

		$viewModel 			= new ViewModel();
		$viewModel->baseUrl = $this->baseUrl;
		$viewModel->setTemplate('requirejs/config.phtml');

		$paths = '{';
		foreach ($this->modules as $moduleName => $path) {
			$paths .= PHP_EOL . "$moduleName : '$path',";
		}

		$viewModel->paths = substr($paths, 0, -1) . '}';

		return $this->getView()->render($viewModel);
	}

	protected function renderMain()
	{
		$this->prioritizeApplications();

		$viewModel = new ViewModel();
		$viewModel->setTemplate('requirejs/main.phtml');

		$dependencies 	= '';
		$arguments 		= array();
		$initializers 	= array();

		if (empty($this->applications)) {
			return '';
		}

		$dependencies 	.= '[';

		foreach ($this->applications as $app) {
			$dependencies 		.= '"'.$app['applicationId'].'",';
			$strippedId 		= str_replace('/', '', $app['applicationId']);
			$arguments[]  		= $strippedId;
			$initializers[]	= $strippedId . '();';
		}

		$dependencies = substr($dependencies, 0, -1) . '], ';

		$viewModel->dependencies 	= $dependencies;
		$viewModel->arguments 	 	= implode(', ', $arguments);
		$viewModel->initializers 	= implode(PHP_EOL, $initializers) . PHP_EOL;

		return $this->getView()->render($viewModel);
	}

	protected function inlineScriptTag($scripts, $attributes = array())
	{
		$scriptTag = '<script type="text/javascript"';

		if (!empty($attributes)) {
			foreach ($attributes as $attr => $val) {
				$scriptTag .= " {$attr}=\"$val\"";
			}
		}

		$scriptTag .= '>' . PHP_EOL;

		foreach ($scripts as $script) {
			$scriptTag .= PHP_EOL . '// ' . $script['description'] . PHP_EOL;
			$scriptTag .= $script['script'] . PHP_EOL;
		}

		$scriptTag .= '</script>';

		return $scriptTag;
	}

	protected function prioritizeApplications()
	{
		if (empty($this->applications)) {
			return $this;
		}

	    $sorter = array();
	    $ret 	= array();

	    reset($this->applications);

	    foreach ($this->applications as $k => $v) {
	        $sorter[$k] = $v['priority'];
	    }

	    asort($sorter);

	    foreach ($sorter as $k => $v) {
	        $ret[$k]=$this->applications[$k];
	    }

	    $this->applications = $ret;

	    return $this;
	}
}
















