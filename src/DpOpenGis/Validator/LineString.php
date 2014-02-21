<?php
/**
 * User: dpeuscher
 * Date: 12.03.13
 */
namespace DpOpenGis\Validator;

// Framework usage
use DpZFExtensions\Validator\AbstractValidator;
use DpOpenGis\ModelInterface\IPointCollection;
use Zend\Validator\ValidatorInterface;

/**
 * Represents a validator that checks if a Point is in a valid state
 */
class LineString extends AbstractValidator implements ValidatorInterface {
	const POINTSINVALID = 'pointsInvalid';

	/**
	 * @var array
	 */
	protected $messageTemplates = array(
		self::POINTSINVALID => "Invalid value for points: %value% is not an instance of IPointCollection",
	);

	/**
	 * @param  mixed $value
	 * @return bool
	 * @throws \Zend\Validator\Exception\RuntimeException If validation of $value is impossible
	 */
	public function _isValidByTypes($value) {
		/**
		 * @var array $value
		 */
		extract($value);
		/** @var IPointCollection $points */
		if (!$points instanceof IPointCollection)
			$this->error(self::POINTSINVALID,var_export($points,true));

		if (array() !== ($this->getMessages()))
			return false;

		return true;
	}
	/**
	 * @param  mixed $value
	 * @return bool
	 * @throws \Zend\Validator\Exception\RuntimeException If validation of $value is impossible
	 */
	public function _isValidByDependencies($value) {
		/**
		 * @var array $value
		 */
		extract($value);
		/** @var IPointCollection $points */

		if (array() !== $this->getMessages())
			return false;

		return true;
	}

}