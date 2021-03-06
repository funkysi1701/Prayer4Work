<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: API.php 3270 2010-10-28 18:21:55Z vipsoft $
 * 
 * @category Piwik_Plugins
 * @package Piwik_VisitFrequency
 */

/**
 *
 * @package Piwik_VisitFrequency
 */
class Piwik_VisitFrequency_API 
{
	static private $instance = null;
	static public function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	public function get( $idSite, $period, $date, $columns = false )
	{
		Piwik::checkUserHasViewAccess( $idSite );
		$archive = Piwik_Archive::build($idSite, $period, $date );
		
		$columns = Piwik::getArrayFromApiParameter($columns);
		$countColumnsRequested = count($columns);
		
		$bounceRateReturningRequested = $averageVisitDurationReturningRequested = $actionsPerVisitReturningRequested = false;
		if(!empty($columns))
		{
			if(($bounceRateReturningRequested = array_search('bounce_rate_returning', $columns)) !== false)
			{
				$columns = array('nb_visits_returning', 'bounce_count_returning');
			}
			elseif(($actionsPerVisitReturningRequested = array_search('nb_actions_per_visit_returning', $columns)) !== false)
			{
				$columns = array('nb_actions_returning', 'nb_visits_returning');
			}
			elseif(($averageVisitDurationReturningRequested = array_search('avg_time_on_site_returning', $columns)) !== false)
			{
				$columns = array('sum_visit_length_returning', 'nb_visits_returning');
			}
		}
		else
		{ 
			$bounceRateReturningRequested = $averageVisitDurationReturningRequested = $actionsPerVisitReturningRequested = true;
			$columns = array( 	'nb_uniq_visitors_returning',
								'nb_visits_returning',
								'nb_actions_returning',
								'max_actions_returning',
								'sum_visit_length_returning',
								'bounce_count_returning',
								'nb_visits_converted_returning',
					);
		}
		$dataTable = $archive->getDataTableFromNumeric($columns);
		
		// Process ratio metrics
		if($bounceRateReturningRequested !== false)
		{
			$dataTable->filter('ColumnCallbackAddColumnPercentage', array('bounce_rate_returning', 'bounce_count_returning', 'nb_visits_returning', 0));
		}
		if($actionsPerVisitReturningRequested !== false)
		{
			$dataTable->filter('ColumnCallbackAddColumnQuotient', array('nb_actions_per_visit_returning', 'nb_actions_returning', 'nb_visits_returning', 1));
		}
		if($averageVisitDurationReturningRequested !== false)
		{
			$dataTable->filter('ColumnCallbackAddColumnQuotient', array('avg_time_on_site_returning', 'sum_visit_length_returning', 'nb_visits_returning', 0));
		}
	
		// If only a computed metrics was requested, we delete other metrics 
		// that we selected only to process this one metric 
		if($countColumnsRequested == 1
			&& ($bounceRateReturningRequested || $averageVisitDurationReturningRequested || $actionsPerVisitReturningRequested)
			) 
		{
			$dataTable->deleteColumns($columns);
		}
		return $dataTable;
	}

	protected function getNumeric( $idSite, $period, $date, $toFetch )
	{
		Piwik::checkUserHasViewAccess( $idSite );
		$archive = Piwik_Archive::build($idSite, $period, $date );
		$dataTable = $archive->getNumeric($toFetch);
		return $dataTable;		
	}

	public function getVisitsReturning( $idSite, $period, $date )
	{
		return $this->getNumeric( $idSite, $period, $date, 'nb_visits_returning');
	}
	
	public function getActionsReturning( $idSite, $period, $date )
	{
		return $this->getNumeric( $idSite, $period, $date, 'nb_actions_returning');
	}
	
	public function getSumVisitsLengthReturning( $idSite, $period, $date )
	{
		return $this->getNumeric( $idSite, $period, $date, 'sum_visit_length_returning');
	}
	
	public function getBounceCountReturning( $idSite, $period, $date )
	{
		return $this->getNumeric( $idSite, $period, $date, 'bounce_count_returning');
	}
	
	public function getConvertedVisitsReturning( $idSite, $period, $date )
	{
		return $this->getNumeric( $idSite, $period, $date, 'nb_visits_converted_returning');
	}
}
