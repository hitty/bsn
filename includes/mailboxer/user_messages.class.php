<?php
class user_messages
{
	protected $messages = array();
	protected $errdesc = array
							(
								'NO_LOGIN' => '����� �� ������',
								'NO_PASSWD' => '������ �� ������',
								'NO_FORMAT' => '�� ������� ���������� � �������',
								'NO_OFROM' => '�� ������� ���������� � ofrom',
								'NO_ATTACHMENT' => '� ������ �� ������� �������� ��������',
								'NOT_SUPPORTED_FORMAT' => '������ �� ��������������: "%s"',
								'NOT_SUPPORTED_OFROM' => 'Ofrom �� ��������������: "%s"',
								'FOUNDED_FORMAT' => '������� ���������� � �������: "%s"',
								'FOUNDED_OFROM' => '������� ���������� � ofrom: "%s"',
								'FOUNDED_LOGIN' => '������ login: "%s"',
								'FOUNDED_PASSWD' => '������ password: "%s"',
								'USED_DEFAULT_OFROM' => '����������� ofrom �� ���������: "%s"',
								'MESSAGE_DELETED' => '������ ������� � �������',
								'AUTHORIZATION_SUCCESS' => '����������� ������ �������',
								'AUTHORIZATION_FAILED' => '����������� ���������. ��������������: ����� "%s", ������ "%s"',
								'FILES_EXTRACTED' => '����� �������� ������� ��������� �� ������. ���������� ����������� ������: %s',
								'ID_AGENT_NOT_FOUND' => '�� ������ ������������� ���������',
								'QUEUE_DELETED' => '������� �������',
								'WORKING_ON_ATTACHMENT' => '�������������� ����: %s',
								'PARSE_TYPE_RECOGNIZED' => '��� ������������ ������� ���: %s',
								'PARSE_TYPE_NOT_RECOGNIZED' => '��� ������������ �� �������� ����� �� ������̣�',
								'PARSE_TYPE_DISABLED' => '������ ��� ������������ �������� �� �������������� ��������',
								'FILE_PARSED' => '���� ���������. ���������: %s �������. ���������������: %s',
								'FILE_PARSED_NO_DATA' => '���� ���������, �� �� ��������� �� ������ ��������'
							);

	public function add($err)
	{
		$args = func_get_args($err);
		array_push($this->messages,$args);
	}

	public function get_all()
	{
		return $this->messages;
	}

	public function get_messages()
	{
		$return = array();

		foreach($this->messages as $stored_message)
		{
			if(!isset($this->errdesc[$stored_message[0]]))
			{
				echo 'UNKNOWN!'."\n\n";
				print_r($stored_message);
				die();
			}
			$args = $stored_message;
			unset($args[0],$args[1]);

			array_push($return,array($stored_message[1], vsprintf($this->errdesc[$stored_message[0]],$args)));
		}
		return $return;
	}
}

class user_messages_sender extends user_messages
{
	function send_messages(&$params)
	{
		global $mailer;

		$prev_mess = array();

		switch($params->type)
		{
			case 'mail':
				$from_addr = $params->from_addr;
				$from_name = $params->from_name;
				$from_subject = $params->subject;
				$date = $params->date_fetched;

				// grabbing prev statuses if present
				if(isset($params->prev_status_node))
				{
					$statuses =& $params->prev_status_node;
					$stages = $statuses->childNodes;
					foreach($stages as $stage)
					{
						$tmp = array();
						foreach($stage->childNodes as $item)
						{
							$tmp2 = array();
							foreach($item->attributes as $val)
							{
								$tmp2[] = koi($val->nodeValue);
							}
							$tmp[] = $tmp2;
						}

						$prev_mess[koi($stage->getAttribute('name'))] = $tmp;
					}
				}
				break;
		}
		$messages = $this->get_messages();


		$compl_from_str = empty($from_name) ? $from_addr : $from_name . ' <' . $from_addr . '>';

		$mailer->addReceipt('vadim@bsn.ru');
		$mailer->sender('bsnrobot@bsn.ru');
		$mailer->src_encoding('koi8-r');
		$mailer->xpriority('normal');
		$mailer->subject('MAILROBOT ANSWER: Re: ' . $from_subject);

		$mailer->compose('���������� ������ ������ bsnrobot@bsn.ru (robot@bsn.ru)');
		$mailer->compose();


		if(!empty($compl_from_str)) $mailer->compose('From: ' . $compl_from_str);
		if(!empty($from_subject)) $mailer->compose('Subject: ' . $from_subject);
		if(!empty($date)) $mailer->compose('Date: ' . $date);


		$mailer->indent(2);
		$mailer->compose();
		$mailer->compose('��������� �� ����� ���������:');

		foreach($prev_mess as $stage_name => $stage_messages)
		{
			$mailer->compose();
			$mailer->indent(4);
			$mailer->compose('STAGE: ' . $stage_name);

			$mailer->indent(6);
			foreach($stage_messages as $message_array)
			{
				$mailer->compose('* ' . implode(':',$message_array),false,true);
			}
		}


		$mailer->compose();
		$mailer->indent(4);
		$mailer->compose('STAGE: ' . $params->current_stage);

		$mailer->indent(6);
		foreach($messages as $message)
		{
			$mailer->compose('* ' . implode(':',$message),false,true);
		}


		$mailer->send();
	}
}

?>
