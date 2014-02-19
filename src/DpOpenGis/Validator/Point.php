<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dpeuscher
 * Date: 12.03.13
 * Time: 14:26
 * To change this template use File | Settings | File Templates.
 */
namespace DpOpenGis\Validator;

// Framework usage
use DpZFExtensions\Validator\AbstractValidator;
use Zend\Validator\ValidatorInterface;

/**
 * Represents a validator that checks if a Point is in a valid state
 */
class Point extends AbstractValidator implements ValidatorInterface {
	const LATINVALID = 'latInvalid';
	const LONINVALID = 'lonInvalid';
	const WRONGLAT = 'wrongLat';
	const WRONGLON = 'wrongLon';

	/**
	 * @var array
	 */
	protected $messageTemplates = array(
		self::LATINVALID => "Invalid value for lat: %value% is not a float",
		self::LONINVALID => "Invalid value for lon: %value% is not a float",
		self::WRONGLAT => "Wrong value for lat: %value% is not between -90 and 90",
		self::WRONGLON => "Wrong value for lon: %value% is not between -180 and 180",
	);

	/**
	 * Returns true if and only if $value meets the validation requirements
	 *
	 * If $value fails validation, then this method returns false, and
	 * getMessages() will return an array of messages that explain why the
	 * validation failed.
	 *
	 * @param  mixed $value
	 * @return bool
	 * @throws \Zend\Validator\Exception\RuntimeException If validation of $value is impossible
	 */
	public function _isValidByTypes($value) {
		/**
		 * @var array $value
		 */
		extract($value);
		/** @var float $lat */
		/** @var float $lon */
		if (!is_float($lat)) $this->error(self::LATINVALID,var_export($lat,true));
		if (!is_float($lon)) $this->error(self::LONINVALID,var_export($lon,true));

		if (array() !== ($this->getMessages()))
			return false;

		return true;
	}
	/**
	 * Returns true if and only if $value meets the validation requirements
	 *
	 * If $value fails validation, then this method returns false, and
	 * getMessages() will return an array of messages that explain why the
	 * validation failed.
	 *
	 * @param  mixed $value
	 * @return bool
	 * @throws \Zend\Validator\Exception\RuntimeException If validation of $value is impossible
	 */
	public function _isValidByDependencies($value) {
		/**
		 * @var array $value
		 */
		extract($value);
		/** @var float $lat */
		/** @var float $lon */

		if ($lat < -90 || $lat > 90) $this->error(self::WRONGLAT,var_export($lat,true));
		if ($lon < -180 || $lon > 180) $this->error(self::WRONGLON,var_export($lon,true));

		if (array() !== $this->getMessages())
			return false;

		return true;
	}

}