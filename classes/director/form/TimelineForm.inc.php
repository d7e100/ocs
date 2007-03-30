<?php

/**
 * TimelineForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 *
 * Form for creating and modifying scheduled conference tracks.
 *
 * $Id$
 */

import('form.Form');

class TimelineForm extends Form {

	/** @var boolean can edit metadata */
	var $canEdit;
	
	/**
	 * Constructor.
	 * @param $trackId int omit for a new track
	 */
	function TimelineForm() {
		$this->canEdit = false;
		if (Validation::isDirector() || Validation::isConferenceManager()) {
			$this->canEdit = true;
		}

		if($this->canEdit) {
			parent::Form('trackDirector/timelineEdit.tpl');
		} else {
			parent::Form('trackDirector/timelineView.tpl');
		}

		// Conference start must happen before conference end
		$this->addCheck(new FormValidatorCustom($this, 'endDate', 'required', 'director.timeline.form.conferenceEndDateBeforeConferenceStart',
			create_function('$endDate,$form',
			'return ($endDate > $form->getData(\'startDate\'));'),
			array(&$this)));

		// Conference start must happen before site move to archive
		$this->addCheck(new FormValidatorCustom($this, 'siteEndDate', 'required', 'director.timeline.form.siteEndDateBeforeConferenceStart',
			create_function('$endDate,$form',
			'return ($endDate > $form->getData(\'startDate\'));'),
			array(&$this)));

		// Conference start must happen after submission close
		$this->addCheck(new FormValidatorCustom($this, 'startDate', 'required', 'director.timeline.form.conferenceStartDateBeforeSubmissionsClose',
			create_function('$startDate,$form',
			'return ($startDate > $form->getData(\'submissionsCloseDate\'));'),
			array(&$this)));

		// Conference site start must happen before site end
		$this->addCheck(new FormValidatorCustom($this, 'siteStartDate', 'required', 'director.timeline.form.siteEndDateBeforeSiteStart',
			create_function('$siteStartDate,$form',
			'return ($siteStartDate < $form->getData(\'siteEndDate\'));'),
			array(&$this)));

		// Conference start must happen after site go-live
		$this->addCheck(new FormValidatorCustom($this, 'siteStartDate', 'required', 'director.timeline.form.conferenceStartBeforeSiteStart',
			create_function('$siteStartDate,$form',
			'return ($siteStartDate < $form->getData(\'startDate\'));'),
			array(&$this)));

		// Move to Conference Archive must come after Last Day of Conf
		$this->addCheck(new FormValidatorCustom($this, 'siteEndDate', 'required', 'director.timeline.form.siteEndBeforeLastDay',
			create_function('$siteEndDate,$form',
			'return ($siteEndDate > $form->getData(\'endDate\'));'),
			array(&$this)));

		// regPresenterOpenDate must be before regPresenterCloseDate
		$this->addCheck(new FormValidatorCustom($this, 'regPresenterOpenDate', 'required', 'director.timeline.form.regPresenterCloseDateBeforeRegPresenterOpenDate',
			create_function('$regPresenterOpenDate,$form',
			'return ($regPresenterOpenDate < $form->getData(\'regPresenterCloseDate\'));'),
			array(&$this)));

		// regReviewerOpenDate must be before regReviewerCloseDate
		$this->addCheck(new FormValidatorCustom($this, 'regReviewerOpenDate', 'required', 'director.timeline.form.regReviewerCloseDateBeforeRegReviewerOpenDate',
			create_function('$regReviewerOpenDate,$form',
			'return ($regReviewerOpenDate < $form->getData(\'regReviewerCloseDate\'));'),
			array(&$this)));

		// Submission CfP must come before Close Submissions
		$this->addCheck(new FormValidatorCustom($this, 'showCFPDate', 'required', 'director.timeline.form.submissionsCloseBeforeCFP',
			create_function('$showCFPDate,$form',
			'return ($showCFPDate < $form->getData(\'submissionsCloseDate\'));'),
			array(&$this)));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$schedConf =& Request::getSchedConf();
		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, null, 'director'), 'user.role.director')));
		$templateMgr->assign('helpTopicId','conference.managementPages.timeline');

		$templateMgr->assign('yearOffsetFuture', SCHED_CONF_DATE_YEAR_OFFSET_FUTURE);

		parent::display();
	}
	
	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$schedConf =& Request::getSchedConf();

		$this->_data = array(
			'siteStartDate' => $schedConf->getStartDate(),
			'siteEndDate' => $schedConf->getEndDate(),

			'startDate' => $schedConf->getSetting('startDate'),
			'endDate' => $schedConf->getSetting('endDate'),

			'regPresenterOpenDate' => $schedConf->getSetting('regPresenterOpenDate'),
			'regPresenterCloseDate' => $schedConf->getSetting('regPresenterCloseDate'),
			'showCFPDate' => $schedConf->getSetting('showCFPDate'),
			'submissionsOpenDate' => $schedConf->getSetting('submissionsOpenDate'),
			'submissionsCloseDate' => $schedConf->getSetting('submissionsCloseDate'),
			'regReviewerOpenDate' => $schedConf->getSetting('regReviewerOpenDate'),
			'regReviewerCloseDate' => $schedConf->getSetting('regReviewerCloseDate'),
			'closeReviewProcessDate' => $schedConf->getSetting('closeReviewProcessDate'),
			'postAbstracts' => $schedConf->getSetting('postAbstracts'),
			'postAbstractsDate' => $schedConf->getSetting('postAbstractsDate'),
			'postPapers' => $schedConf->getSetting('postPapers'),
			'postPapersDate' => $schedConf->getSetting('postPapersDate'),
			'delayOpenAccess' => $schedConf->getSetting('delayOpenAccess'),
			'delayOpenAccessDate' => $schedConf->getSetting('delayOpenAccessDate'),
			'closeComments' => $schedConf->getSetting('closeComments'),
			'closeCommentsDate' => $schedConf->getSetting('closeCommentsDate')
		);
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserDateVars(array(
			'siteStartDate', 'siteEndDate',
			'startDate', 'endDate',
			'regPresenterOpenDate', 'regPresenterCloseDate',
			'showCFPDate',
			'submissionsOpenDate', 'submissionsCloseDate',
			'regReviewerOpenDate', 'regReviewerCloseDate', 'closeReviewProcessDate',
			'postAbstractsDate',
			'postPapersDate',
			'delayOpenAccessDate',
			'closeCommentsDate'
		));

		$this->readUserVars(array(
			'postAbstracts',
			'postPapers',
			'delayOpenAccess',
			'closeComments'
		));
	}
	
	/**
	 * Save track.
	 */
	function execute() {
		$schedConfDao =& DAORegistry::getDao('SchedConfDAO');
		$schedConf = &Request::getSchedConf();
		
		import('conference.log.ConferenceLog');
		import('conference.log.ConferenceEventLogEntry');

		//
		// Don't log these, since they aren't particularly nefarious.
		//
		
		// Website start date and end date.
		
		if($schedConf->getStartDate() != $this->_data['siteStartDate']) {
			$schedConf->setStartDate($this->_data['siteStartDate']);
			$schedConfDao->updateSchedConf($schedConf);
		}

		if($schedConf->getEndDate() != $this->_data['siteEndDate']) {
			$schedConf->setEndDate($this->_data['siteEndDate']);
			$schedConfDao->updateSchedConf($schedConf);
		}
		
		//
		// Log the rest.
		//
		
		// Physical scheduled conference start date and end date
		if($schedConf->getSetting('startDate') != $this->_data['startDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.startDateChanged',
				array('oldStartDate' => $schedConf->getSetting('startDate'),
					'newStartDate' => $this->_data['startDate']));
			$schedConf->updateSetting('startDate', $this->_data['startDate'], 'date');
		}

		if($schedConf->getSetting('endDate') != $this->_data['endDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.endDateChanged',
				array('oldEndDate' => $schedConf->getSetting('endDate'),
					'newEndDate' => $this->_data['endDate']));
			$schedConf->updateSetting('endDate', $this->_data['endDate'], 'date');
		}

		if($schedConf->getSetting('regPresenterOpenDate') != $this->_data['regPresenterOpenDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regPresenterOpenDateChanged',
				array('oldRegPresenterOpenDate' => $schedConf->getSetting('regPresenterOpenDate'),
					'newRegPresenterOpenDate' => $this->_data['regPresenterOpenDate']));
			$schedConf->updateSetting('regPresenterOpenDate', $this->_data['regPresenterOpenDate'], 'date');
		}
		if($schedConf->getSetting('regPresenterCloseDate') != $this->_data['regPresenterCloseDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regPresenterCloseDateChanged',
				array('oldRegPresenterCloseDate' => $schedConf->getSetting('regPresenterCloseDate'),
					'newRegPresenterCloseDate' => $this->_data['regPresenterCloseDate']));
			$schedConf->updateSetting('regPresenterCloseDate', $this->_data['regPresenterCloseDate'], 'date');
		}
		if($schedConf->getSetting('showCFPDate') != $this->_data['showCFPDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.showCFPDateChanged',
				array('oldShowCFPDate' => $schedConf->getSetting('showCFPDate'),
					'newShowCFPDate' => $this->_data['showCFPDate']));
			$schedConf->updateSetting('showCFPDate', $this->_data['showCFPDate'], 'date');
		}

		if($schedConf->getSetting('submissionsOpenDate') != $this->_data['submissionsOpenDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.submissionsOpenDateChanged',
				array('oldSubmissionsOpenDate' => $schedConf->getSetting('submissionsOpenDate'),
					'newSubmissionsOpenDate' => $this->_data['submissionsOpenDate']));
			$schedConf->updateSetting('submissionsOpenDate', $this->_data['submissionsOpenDate'], 'date');
		}
		if($schedConf->getSetting('submissionsCloseDate') != $this->_data['submissionsCloseDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.submissionsCloseDateChanged',
				array('oldSubmissionsCloseDate' => $schedConf->getSetting('submissionsCloseDate'),
					'newSubmissionsCloseDate' => $this->_data['submissionsCloseDate']));
			$schedConf->updateSetting('submissionsCloseDate', $this->_data['submissionsCloseDate'], 'date');
		}
		if($schedConf->getSetting('regReviewerOpenDate') != $this->_data['regReviewerOpenDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regReviewerOpenDateChanged',
				array('oldRegReviewerOpenDate' => $schedConf->getSetting('regReviewerOpenDate'),
					'newRegReviewerOpenDate' => $this->_data['regReviewerOpenDate']));
			$schedConf->updateSetting('regReviewerOpenDate', $this->_data['regReviewerOpenDate'], 'date');
		}
		if($schedConf->getSetting('regReviewerCloseDate') != $this->_data['regReviewerCloseDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regReviewerCloseDateChanged',
				array('oldRegReviewerCloseDate' => $schedConf->getSetting('regReviewerCloseDate'),
					'newRegReviewerCloseDate' => $this->_data['regReviewerCloseDate']));
			$schedConf->updateSetting('regReviewerCloseDate', $this->_data['regReviewerCloseDate'], 'date');
		}
		if($schedConf->getSetting('postAbstractsDate') != $this->_data['postAbstractsDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postAbstractsDateChanged',
				array(	'oldPostAbstractsDate' => $schedConf->getSetting('postAbstractsDate'),
					'newPostAbstractsDate' => $this->_data['postAbstractsDate']));
			$schedConf->updateSetting('postAbstractsDate', $this->_data['postAbstractsDate'], 'date');
		}
		if($schedConf->getSetting('postAbstracts') != $this->_data['postAbstracts']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postAbstractsChanged',
				array(	'oldPostAbstracts' => Locale::translate($schedConf->getSetting('postAbstracts')?'common.true':'common.false'),
					'newPostAbstracts' => Locale::translate($this->_data['postAbstracts'])?'common.true':'common.false'));
			$schedConf->updateSetting('postAbstracts', $this->_data['postAbstracts'], 'bool');
		}
		if($schedConf->getSetting('postPapersDate') != $this->_data['postPapersDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postPapersDateChanged',
				array(	'oldPostPapersDate' => $schedConf->getSetting('postPapersDate'),
					'newPostPapersDate' => $this->_data['postPapersDate']));
			$schedConf->updateSetting('postPapersDate', $this->_data['postPapersDate'], 'date');
		}
		if($schedConf->getSetting('postPapers') != $this->_data['postPapers']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postPapersChanged',
				array(	'oldPostPapers' => Locale::translate($schedConf->getSetting('postPapers')?'common.true':'common.false'),
					'newPostPapers' => Locale::translate($this->_data['postPapers']?'common.true':'common.false')));
			$schedConf->updateSetting('postPapers', $this->_data['postPapers'], 'bool');
		}
		if($schedConf->getSetting('delayOpenAccessDate') != $this->_data['delayOpenAccessDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.delayOpenAccessDateChanged',
				array(	'oldDelayOpenAccessDate' => $schedConf->getSetting('delayOpenAccessDate'),
					'newDelayOpenAccessDate' => $this->_data['delayOpenAccessDate']));
			$schedConf->updateSetting('delayOpenAccessDate', $this->_data['delayOpenAccessDate'], 'date');
		}
		if($schedConf->getSetting('delayOpenAccess') != $this->_data['delayOpenAccess']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.delayOpenAccessChanged',
				array(	'oldDelayOpenAccess' => Locale::translate($schedConf->getSetting('delayOpenAccess')?'common.true':'common.false'),
					'newDelayOpenAccess' => Locale::translate($this->_data['delayOpenAccess']?'common.true':'common.false')));
			$schedConf->updateSetting('delayOpenAccess', $this->_data['delayOpenAccess'], 'bool');
		}
		if($schedConf->getSetting('closeCommentsDate') != $this->_data['closeCommentsDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.closeCommentsDateChanged',
				array(	'oldCloseCommentsDate' => $schedConf->getSetting('closeCommentsDate'),
					'newCloseCommentsDate' => $this->_data['closeCommentsDate']));
			$schedConf->updateSetting('closeCommentsDate', $this->_data['closeCommentsDate'], 'date');
		}
		if($schedConf->getSetting('closeComments') != $this->_data['closeComments']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.closeCommentsChanged',
				array(	'oldCloseComments' => Locale::translate($schedConf->getSetting('closeComments')?'common.true':'common.false'),
					'newCloseComments' => Locale::translate($this->_data['closeComments']?'common.true':'common.false')));
			$schedConf->updateSetting('closeComments', $this->_data['closeComments'], 'bool');
		}
	}
}

?>
