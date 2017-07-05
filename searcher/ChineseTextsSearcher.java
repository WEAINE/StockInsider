import java.io.File;
import java.util.ArrayList;
import java.util.regex.Pattern;
import java.util.regex.Matcher;
import java.lang.StringBuilder;
import java.lang.Runtime;
import org.apache.commons.codec.digest.DigestUtils;
import org.apache.lucene.store.Directory;
import org.apache.lucene.store.FSDirectory;
import org.apache.lucene.index.DirectoryReader;
import org.apache.lucene.index.Term;
import org.apache.lucene.document.Document;
import org.apache.lucene.search.IndexSearcher;
import org.apache.lucene.search.BooleanQuery;
import org.apache.lucene.search.BooleanClause.Occur;
import org.apache.lucene.search.PhraseQuery;
import org.apache.lucene.search.RegexpQuery;
import org.apache.lucene.search.ScoreDoc;

public class ChineseTextsSearcher {
	private String source;
	private String startYear;
	private String endYear;
	private String field;
	private ArrayList<String[]> multiTerms = new ArrayList<String[]>();
	private String searchHex;

	public static void main(String[] args) throws Exception {
		ChineseTextsSearcher chineseTextsSearcher = new ChineseTextsSearcher();
		if (!chineseTextsSearcher.parseArgs(args)) {
			System.exit(-1);
			return;
		}

		File indexDir = new File("/home/ssd/StockInsider/index/" + chineseTextsSearcher.source);
		Directory directory = FSDirectory.open(indexDir.toPath());
		DirectoryReader directoryReader;
		try { directoryReader = DirectoryReader.open(directory); }
		catch (Exception e) {
			System.exit(-2);
			return;
		}

		BooleanQuery.Builder queryBuilder = new BooleanQuery.Builder();
		BooleanQuery.Builder dateQueryBuilder = new BooleanQuery.Builder();
		for (String[] terms : chineseTextsSearcher.multiTerms) {
			PhraseQuery.Builder phraseQueryBuilder = new PhraseQuery.Builder();
			for (String term : terms) phraseQueryBuilder.add(new Term(chineseTextsSearcher.field, term));
			PhraseQuery phraseQuery = phraseQueryBuilder.build();
			
			queryBuilder.add(phraseQuery, Occur.MUST);
		}
		for (int i = Integer.parseInt(chineseTextsSearcher.startYear); i <= Integer.parseInt(chineseTextsSearcher.endYear); i++) {
			RegexpQuery regexpQuery = new RegexpQuery(new Term("date", Integer.toString(i) + ".*"));
			dateQueryBuilder.add(regexpQuery, Occur.SHOULD);
		}
		BooleanQuery dateQuery = dateQueryBuilder.build();
		queryBuilder.add(dateQuery, Occur.MUST);
		BooleanQuery query = queryBuilder.build();

		IndexSearcher indexSearcher = new IndexSearcher(directoryReader);
		ScoreDoc[] hits = indexSearcher.search(query, 100).scoreDocs;

		boolean hitsExist = (hits.length != 0);
		if (hitsExist) Runtime.getRuntime().exec("mkdir /home/weaine/dev/StockInsider/web/result/cache/" + chineseTextsSearcher.searchHex);

		while (hits.length != 0) {
			for (ScoreDoc scoreDoc : hits) {
				Document hitDoc = indexSearcher.doc(scoreDoc.doc);
				String path = hitDoc.get("path");
				String filename = path.substring(path.lastIndexOf("/"), path.length());

				Runtime.getRuntime().exec("ln -s " + path + " /home/weaine/dev/StockInsider/web/result/cache/" + chineseTextsSearcher.searchHex + filename);
			}

			hits = indexSearcher.searchAfter(hits[hits.length - 1], query, 100).scoreDocs;
		}

		if (hitsExist) Runtime.getRuntime().exec("chmod 745 /home/weaine/dev/StockInsider/web/result/cache/" + chineseTextsSearcher.searchHex);

		directoryReader.close();
		directory.close();
	}

	private boolean parseArgs(String[] args) throws Exception {
		if (args.length < 5) {
			showTips();
			return false;
		}

		if (args[0].equals("-announcements_companies")) this.source = "announcements_companies";
		else if (args[0].equals("-announcements_CSRCRC")) this.source = "announcements_CSRCRC";
		else if (args[0].equals("-replies_CSRC")) this.source = "replies_CSRC";
		else {
			showTips();
			return false;
		}

		this.startYear = args[1].substring(2);
		this.endYear = args[2].substring(2);

		if (args[3].equals("--path")) this.field = "path";
		else if (args[3].equals("--date")) this.field = "date";
		else if (args[3].equals("--title")) this.field = "title";
		else if (args[3].equals("--content")) this.field = "content";
		else {
			showTips();
			return false;
		}

		StringBuilder stringBuilder = new StringBuilder();
		stringBuilder.append(this.source);
		stringBuilder.append(this.startYear);
		stringBuilder.append(this.endYear);
		stringBuilder.append(this.field);

		Pattern chinesePattern = Pattern.compile("[\\u4e00-\\u9fa5]");
		Pattern numberPattern = Pattern.compile("\\d+");

		for (int i = 4; i < args.length; i++) {
			stringBuilder.append(args[i]);

			Matcher chineseMatcher = chinesePattern.matcher(args[i]);
			Matcher numberMatcher = numberPattern.matcher(args[i]);

			ArrayList<String> termsList = new ArrayList<String>();
                	while (chineseMatcher.find()) termsList.add(chineseMatcher.group(0));
                	while (numberMatcher.find()) termsList.add(numberMatcher.group(0));

			String[] terms = new String[termsList.size()];
			termsList.toArray(terms);
			this.multiTerms.add(terms);
		}

		this.searchHex = DigestUtils.md5Hex(stringBuilder.toString().getBytes());

		return true;
	}

	private void showTips() {
		System.out.println("用法：java [-classpath CLASSPATH] ChineseTextsSearcher -source --startyear --endyear --field \"query string 1\" \"query string 2\" ...");
		System.out.println("数据源source：当前支持参数announcements_companies、announcements_CSRCRC、replies_CSRC");
		System.out.println("数据时效起始值startyear：数字年份");
		System.out.println("数据时效终止值endyear：数字年份");
		System.out.println("搜索域field：当前支持参数path、date、title、content");
	}
}
