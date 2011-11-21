<?php

/**
 * Seodata
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Models_Seodata extends Application_Model_Models_Abstract {

	protected $_seoHead   = '';

	protected $_seoBottom = '';

	protected $_seoTop    = '';

	public function getSeoHead() {
		return $this->_seoHead;
	}

	public function setSeoHead($seoHead) {
		$this->_seoHead = $seoHead;
		return $this;
	}

	public function getSeoBottom() {
		return $this->_seoBottom;
	}

	public function setSeoBottom($seoBottom) {
		$this->_seoBottom = $seoBottom;
		return $this;
	}

	public function getSeoTop() {
		return $this->_seoTop;
	}

	public function setSeoTop($seoTop) {
		$this->_seoTop = $seoTop;
		return $this;
	}
}

