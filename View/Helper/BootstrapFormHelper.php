<?php
App::uses('FormHelper', 'View/Helper');
App::uses('Set', 'Utility');

class BootstrapFormHelper extends FormHelper {

	public $helpers = array('Html');

	public function textarea($fieldName, $options, $before = false) {
		if ($before) {
			if ('textarea' === $options['type']) {
				$options += array('cols' => false, 'rows' => '3');
			}
			return $options;
		} else {
			return parent::textarea($fieldName, $options);
		}
	}

	public function uneditable($fieldName, $options, $before = false) {
		if ($before) {
			$class =  explode(' ', $this->_extractOption('class', $options));
			if (in_array('uneditable-input', $class)) {
				$options['type'] = 'uneditable';
			}
			return $options;
		} else {
			return $this->Html->tag('span', $options['value'], $options['class']);
		}
	}

	public function addon($fieldName, $options, $before = false) {
		if ($before) {
			$prepend = $this->_extractOption('prepend', $options);
			$append = $this->_extractOption('append', $options);
			if ($prepend || $append) {
				$options['_type'] = $options['type'];
				$options['type'] = 'addon';
			}
			return $options;
		} else {
			$type = $options['_type'];
			unset($options['_type']);

			$default = array('wrap' => 'span', 'class' => 'add-on');
			$divOptions = array();
			foreach (array('prepend', 'append') as $addon) {
				$option = (array) $this->_extractOption($addon, $options);
				$$addon = null;
				if ($option) {
					unset($options[$addon]);

					array_push($option, array());
					list($text, $addonOptions) = $option;
					$addonOptions += $default;

					$wrap = $addonOptions['wrap'];
					unset($addonOptions['wrap']);

					$$addon = $this->Html->tag($wrap, $text, $addonOptions);
					$divOptions = $this->addClass($divOptions, 'input-' . $addon);
				}
			}
			$out = $prepend . $this->{$type}($fieldName, $options) . $append;
			return $this->Html->tag('div', $out, $divOptions);
		}
	}

	public function checkbox($fieldName, $options, $before = false) {
		if ($before) {
			if ('checkbox' === $options['type']) {
				$options['_div'] = $this->_extractOption('div', $options);
				$options['_label'] = $this->_extractOption('label', $options);
				$options['_after'] = $this->_extractOption('after', $options);
				if (false === $options['div']) {
					$options['label'] = false;
				} else {
					$options['after'] = null;
				}
			}
			return $options;
		} else {
			$label = $options['_label'];
			$after = $options['_after'];

			if ($options['_div']) {
				$label['text'] = $after;
				$label['class'] = null;
			}

			unset($options['_after']);
			unset($options['_label']);
			unset($options['_div']);

			$label = $this->addClass($label, 'checkbox');
			$out = parent::checkbox($fieldName, $options) . $label['text'];
			return $this->label($fieldName, $out, array('class' => $label['class']));
		}
	}

	public function radio($fieldName, $radioOptions, $options) {
		$options['legend'] = false;
		$options['separator'] = "\n";
		$out = parent::radio($fieldName, $radioOptions, $options);
		$out = $this->_restructureLabel($out, array('class' => 'radio'));
		return $out;
	}

	public function select($fieldName, $options = array(), $attributes = array()) {
		$multiple = $this->_extractOption('multiple', $attributes);
		$checkbox = explode(' ', $multiple);
		$attributes['multiple'] = $checkbox[0];
		$out = parent::select($fieldName, $options, $attributes);
		if ('checkbox' === $checkbox[0]) {
			$out = $this->_restructureLabel($out, array('class' => $multiple));
		}
		return $out;
	}

	protected function _restructureLabel($out, $options = array()) {
		$out = explode("\n", $out);
		foreach ($out as $key => &$_out) {
			$input = strip_tags($_out, '<input>');
			if ($input) {
				$_out = $this->Html->tag('label', $input, $options);
			}
		}
		return implode("\n", $out);
	}

	public function create($model = null, $options = array()) {
		$class = explode(' ', $this->_extractOption('class', $options));
		$inputDefaults = $this->_extractOption('inputDefaults', $options, array());

		if (in_array('form-search', $class) || in_array('form-inline', $class)) {
			$options['inputDefaults'] = Set::merge($inputDefaults, array(
				'div' => false,
				'label' => false,
			));
		}
		elseif (in_array('form-horizontal', $class)) {
			$options['inputDefaults'] = Set::merge($inputDefaults, array(
				'div' => 'control-group',
			));
		}
		else {
			$options['inputDefaults'] = Set::merge($inputDefaults, array(
				'div' => false,
			));
		}

		return parent::create($model, $options);
	}

	public function submit($caption = null, $options = array()) {
		$default = array(
			'type' => 'submit',
			'class' => 'btn',
			'div' => 'form-actions',
		);
		$options = Set::merge($default, $options);
		$div = $options['div'];
		unset($options['div']);

		$out = $this->button($caption, $options);
		if ($div) {
			$out = $this->Html->div($div, $out);
		}
		return $out;
	}

	public function input($fieldName, $options = array()) {
		$options = array_merge(
			array('format' => array('before', 'label', 'between', 'input', 'error', 'after')),
			$this->_inputDefaults,
			$options
		);
		$options = $this->_initInputField($fieldName, $options);
		$options = $this->_setType($options);

		$options['label'] = $this->_extractOption('label', $options);
		if (false !== $options['label'] && !is_array($options['label'])) {
			$options['label'] = array('text' => $options['label'], 'class' => null);
		}
		$options['div'] = $this->_extractOption('div', $options);
		if (false !== $options['label'] && false !== $options['div']) {
			$options['label'] = $this->addClass($options['label'], 'control-label');
		}

		$options = $this->uneditable($fieldName, $options, true);
		$options = $this->addon($fieldName, $options, true);
		$options = $this->textarea($fieldName, $options, true);
		$options = $this->checkbox($fieldName, $options, true);
		$options = $this->_controlGroupStates($fieldName, $options);
		$options = $this->_buildAfter($options);

		$disabled = $this->_extractOption('disabled', $options, false);
		if ($disabled) {
			$options = $this->addClass($options, 'disabled');
		}

		$div = $this->_extractOption('div', $options);
		$options['div'] = false;
		$label = $this->_extractOption('label', $options);
		$options['label'] = false;

		$hidden = $this->_hidden($fieldName, $options);
		if ($hidden) {
			$options['hiddenField'] = false;
		}

		$out = parent::input($fieldName, $options);

		if ($div) {
			$out = $this->Html->div('controls', $out);
		}
		$out = $hidden . $out;

		if (false !== $label) {
			$out = $this->label($fieldName, $label['text'], array('class' => $label['class'])) . $out;
		}
		if ($div) {
			$out = $this->Html->div($div, $out);
		}
		return $out;
	}

	protected function _setType($options) {
		$modelKey = $this->model();
		$fieldKey = $this->field();

		if (!isset($options['type'])) {
			$magicType = true;
			$options['type'] = 'text';
			if (isset($options['options'])) {
				$options['type'] = 'select';
			} elseif (in_array($fieldKey, array('psword', 'passwd', 'password'))) {
				$options['type'] = 'password';
			} elseif (isset($options['checked'])) {
				$options['type'] = 'checkbox';
			} elseif ($fieldDef = $this->_introspectModel($modelKey, 'fields', $fieldKey)) {
				$type = $fieldDef['type'];
				$primaryKey = $this->fieldset[$modelKey]['key'];
			}

			if (isset($type)) {
				$map = array(
					'string' => 'text', 'datetime' => 'datetime',
					'boolean' => 'checkbox', 'timestamp' => 'datetime',
					'text' => 'textarea', 'time' => 'time',
					'date' => 'date', 'float' => 'number',
					'integer' => 'number'
				);

				if (isset($this->map[$type])) {
					$options['type'] = $this->map[$type];
				} elseif (isset($map[$type])) {
					$options['type'] = $map[$type];
				}
				if ($fieldKey == $primaryKey) {
					$options['type'] = 'hidden';
				}
				if (
					$options['type'] === 'number' &&
					$type === 'float' &&
					!isset($options['step'])
				) {
					$options['step'] = 'any';
				}
			}
			if (preg_match('/_id$/', $fieldKey) && $options['type'] !== 'hidden') {
				$options['type'] = 'select';
			}

			if ($modelKey === $fieldKey) {
				$options['type'] = 'select';
				if (!isset($options['multiple'])) {
					$options['multiple'] = 'multiple';
				}
			}
		}
		return $options;
	}

	protected function _buildAfter($options) {
		$outInline = array();
		$inlines = (array) $this->_extractOption('helpInline', $options, array());
		if ($inlines) {
			unset($options['helpInline']);
		}
		foreach ($inlines as $inline) {
			$outInline[] = $this->help($inline, array('type' => 'inline'));
		}
		$outInline = implode(' ', $outInline);

		$outBlock = array();
		$blocks = (array) $this->_extractOption('helpBlock', $options, array());
		if ($blocks) {
			unset($options['helpBlock']);
		}
		foreach ($blocks as $block) {
			$outBlock[] = $this->help($block, array('type' => 'block'));
		}
		$outBlock = implode("\n", $outBlock);

		$options['after'] = $outInline . "\n" . $outBlock . "\n" . $this->_extractOption('after', $options);
		return $options;
	}

	protected function _controlGroupStates($fieldName, $options) {
		if (false !== $options['div']) {
			$inlines = (array) $this->_extractOption('helpInline', $options, array());
			foreach ($options as $key => $value) {
				if (in_array($key, array('warning', 'success'))) {
					unset($options[$key]);
					array_unshift($inlines, $value);
					$options = $this->addClass($options, $key, 'div');
				}
			}
			$options['helpInline'] = $inlines;
		}
		if ($this->error($fieldName)) {
			$error = $this->_extractOption('error', $options, array());
			$options['error'] = array_merge($error, array(
				'attributes' => array(
					'wrap' => 'span',
					'class' => 'help-inline error-message',
				),
			));
			$options = $this->addClass($options, 'error', 'div');
		}
		return $options;
	}

	protected function _hidden($fieldName, $options) {
		if (!in_array($options['type'], array('checkbox', 'radio', 'select'))) {
			return null;
		}
		$multiple = $this->_extractOption('multiple', $options);
		$checkbox = explode(' ', $multiple);
		if ('select' === $options['type'] && 'checkbox' !== $checkbox[0]) {
			return null;
		}

		$out = null;
		$hiddenField = $this->_extractOption('hiddenField', $options, true);
		if ($hiddenField) {
			if (!isset($options['value']) || $options['value'] === '') {
				$out = $this->hidden($fieldName, array(
					'id' => $options['id'] . '_', 'value' => '', 'name' => $options['name']
				));
			}
		}
		return $out;
	}

	public function help($text, $options) {
		$classMap = array(
			'inline' => array('wrap' => 'span', 'class' => 'help-inline'),
			'block' => array('wrap' => 'p', 'class' => 'help-block'),
		);
		$options += array('type' => 'inline');
		$options += $this->_extractOption($options['type'], $classMap, array());
		unset($options['type']);
		$wrap = $options['wrap'];
		unset($options['wrap']);
		return $this->Html->tag($wrap , $text, $options);
	}
}
