<?php
/**
 * CYBER INCIDENT SYSTEM — AI THREAT ANALYSIS ENGINE
 * Rule-based simulation of security incident analysis.
 */

function runAIAnalysis($type, $severity, $description, $title) {
    // Risk score calculation
    $base_scores = [
        'phishing'            => 65,
        'malware'             => 75,
        'unauthorized_access' => 80,
        'data_breach'         => 90,
        'other'               => 40
    ];

    $severity_boost = [
        'low'      => 0,
        'medium'   => 10,
        'high'     => 20,
        'critical' => 30
    ];

    // Keyword-based boost (simple NLP simulation)
    $keywords_high = ['password', 'credential', 'admin', 'root', 'sensitive', 'confidential', 'database', 'backup'];
    $keywords_medium = ['click', 'link', 'attachment', 'download', 'unknown', 'suspicious'];

    $desc_lower = strtolower($description . ' ' . $title);
    $keyword_boost = 0;

    foreach ($keywords_high as $kw) {
        if (strpos($desc_lower, $kw) !== false) {
            $keyword_boost += 8;
        }
    }
    foreach ($keywords_medium as $kw) {
        if (strpos($desc_lower, $kw) !== false) {
            $keyword_boost += 4;
        }
    }

    $risk_score = min(100, ($base_scores[$type] ?? 40) + ($severity_boost[$severity] ?? 0) + $keyword_boost);

    // Risk label
    if ($risk_score >= 85)      $risk_label = 'CRITICAL';
    elseif ($risk_score >= 70)  $risk_label = 'HIGH';
    elseif ($risk_score >= 50)  $risk_label = 'MEDIUM';
    else                        $risk_label = 'LOW';

    // Type-specific analysis templates
    $type_analyses = [
        'phishing' => [
            'summary'   => 'Phishing attack vector detected. Social engineering attempt to harvest credentials or deliver malware via deceptive communication.',
            'mitre'     => 'MITRE ATT&CK: T1566 - Phishing',
            'actions'   => [
                'Do NOT click any links or download attachments from the suspicious email',
                'Forward the email to security@company.com for header analysis',
                'Reset your password immediately if credentials were entered',
                'Enable Multi-Factor Authentication (MFA) on all accounts',
                'Report the sender\'s email domain to IT for blacklisting'
            ]
        ],
        'malware' => [
            'summary'   => 'Malicious software infection detected. System integrity may be compromised. Lateral movement and data exfiltration are possible.',
            'mitre'     => 'MITRE ATT&CK: T1204 - User Execution / T1059 - Command Execution',
            'actions'   => [
                'IMMEDIATELY disconnect the affected device from the network',
                'Do not shut down the machine — preserve forensic evidence',
                'Contact IT Security for disk imaging before any cleanup',
                'Run an offline antivirus scan using a bootable USB',
                'Audit recently accessed files and network connections',
                'Change all passwords from a CLEAN, unaffected device'
            ]
        ],
        'unauthorized_access' => [
            'summary'   => 'Unauthorized access attempt or breach detected. Authentication controls may have been bypassed. Account takeover is a likely goal.',
            'mitre'     => 'MITRE ATT&CK: T1078 - Valid Accounts / T1110 - Brute Force',
            'actions'   => [
                'Lock the affected account immediately via Active Directory',
                'Review login audit logs for the past 72 hours',
                'Force password reset for the compromised account and any shared accounts',
                'Enable MFA and review MFA bypass policies',
                'Check for any new user accounts, scheduled tasks, or persistence mechanisms',
                'Notify the user and confirm whether the access was authorized'
            ]
        ],
        'data_breach' => [
            'summary'   => 'CRITICAL: Potential data breach identified. Sensitive organizational or personal data may have been exfiltrated. Regulatory implications likely.',
            'mitre'     => 'MITRE ATT&CK: T1041 - Exfiltration Over C2 / T1567 - Exfiltration to Web Service',
            'actions'   => [
                'ESCALATE IMMEDIATELY to CISO and Data Protection Officer (DPO)',
                'Initiate Incident Response Plan — activate IR team now',
                'Identify what data was accessed and its classification level',
                'Preserve all logs — do NOT alter or delete any system artifacts',
                'Assess GDPR / PDPA notification obligations (72-hour window)',
                'Contain the breach: revoke access, block exfiltration channels',
                'Prepare internal and external breach notification drafts'
            ]
        ],
        'other' => [
            'summary'   => 'Security incident logged and queued for triage. Nature of threat is unclassified — manual investigation required.',
            'mitre'     => 'MITRE ATT&CK: Tactic undetermined — requires analyst review',
            'actions'   => [
                'Assign to a security analyst for manual triage within 24 hours',
                'Gather additional evidence: screenshots, logs, timestamps',
                'Determine if the incident affects other users or systems',
                'Classify the incident type after investigation',
                'Update the incident status as investigation progresses'
            ]
        ]
    ];

    $type_data = $type_analyses[$type] ?? $type_analyses['other'];

    // Severity-specific urgency note
    $urgency = '';
    if ($severity === 'critical')     $urgency = '⚠️ CRITICAL PRIORITY — Escalate to CISO within 15 minutes.';
    elseif ($severity === 'high')     $urgency = '🔶 HIGH PRIORITY — Response required within 2 hours.';
    elseif ($severity === 'medium')   $urgency = '🔷 MEDIUM PRIORITY — Investigate within 24 hours.';
    else                              $urgency = '🔹 LOW PRIORITY — Review within 72 hours.';

    $full_text = "[Risk Score: $risk_score/100 — $risk_label] " .
                 $type_data['summary'] . ' ' .
                 $type_data['mitre'] . '. ' .
                 $urgency;

    return [
        'risk_score'          => $risk_score,
        'risk_label'          => $risk_label,
        'full_text'           => $full_text,
        'recommended_actions' => $type_data['actions'],
        'mitre_tactic'        => $type_data['mitre']
    ];
}
